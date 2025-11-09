# Overtime Auto-Logout - Server-Side Implementation

## Problem koji rešava

**PITANJE**: Da li se automatski logout izvršava ako korisnik ugasi računar?

**ORIGINALNI ODGOVOR**: NE ❌

**Razlog**: Originalna implementacija koristi JavaScript `setTimeout` u frontend-u. Ako korisnik:
- Ugasi računar
- Zatvori browser
- Izgubi internet konekciju
- Browser crashuje

→ Timer se NEĆE izvršiti → Korisnik ostaje prijavljen u sistemu

## Rešenje - Server-Side Auto-Logout

Kreiran je novi Artisan Command: `AutoLogoutOvertimeUsers.php`

### Kako radi:

Command se izvršava **svakog minuta** (via Laravel Scheduler) i proverava:

1. ✅ Da li je danas radni dan (`overtime_working_days` setting)
2. ✅ Da li je prošlo vreme za overtime proveru (`overtime_check_time` setting, npr. 15:30)
3. ✅ Nalazi sve korisnike koji:
   - Imaju status `Status = 'Prijavljen'`
   - Imaju popunjeno `overtime_prompt_shown_at` (znači da im je modal prikazan)
   - NISU odgovorili na modal (prošlo više od `overtime_prompt_timeout` minuta)
   - `last_activity_at` je NULL ili JE STARIJE od `overtime_prompt_shown_at`

4. ✅ Automatski ih odjavljuje:
   - Zatvara `TimeLog` sa razlogom "Аутоматска одјава (одсуство одговора на присуство)"
   - Postavlja `Status = 'Odjavljen'`
   - Briše sve aktivne sesije korisnika
   - Loguje sve akcije u `storage/logs/laravel.log`

### Logika provere:

```php
// Korisnik će biti odjavljen ako:
$minutesSincePrompt > $promptTimeout AND
(!$lastActivityAt OR $lastActivityAt <= $promptShownAt)
```

**Primer:**
- Modal prikazan u 15:35
- Timeout je 10 minuta
- Korisnik ugasi računar u 15:36 (NE klikne "Da")
- Server-side Command se izvršava svakog minuta
- U 15:46 (11 minuta od prikaza modala), Command detektuje:
  - `overtime_prompt_shown_at` = 15:35
  - `last_activity_at` = NULL ili <= 15:35
  - Prošlo je 11 minuta > 10 minuta (timeout)
  - ✅ Korisnik se automatski odjavljuje

## Instalacija i Pokretanje

### 1. Registruj Command (već urađeno)

Command je već dodat u `routes/console.php`:

```php
Schedule::command('users:auto-logout-overtime')->everyMinute();
```

### 2. Pokreni Laravel Scheduler

Laravel Scheduler mora da radi da bi command-a izvršavali svakog minuta.

#### Opcija A: Cron Job (Production - Linux)

Dodaj u crontab:

```bash
crontab -e
```

Dodaj liniju:

```bash
* * * * * cd /path/to/employee-attendance && php artisan schedule:run >> /dev/null 2>&1
```

**VAŽNO**: Zameni `/path/to/employee-attendance` sa stvarnom putanjom do projekta.

#### Opcija B: Artisan Command (Development/Testing)

Pokreni u terminalu (ostavi da radi):

```bash
php artisan schedule:work
```

Ova komanda simulira cron i izvršava scheduled task-ove svakog minuta.

#### Opcija C: Supervisor (Production - preporučeno)

Kreiraj supervisor config `/etc/supervisor/conf.d/laravel-scheduler.conf`:

```ini
[program:laravel-scheduler]
process_name=%(program_name)s
command=php /path/to/employee-attendance/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/laravel-scheduler.log
```

Restart supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-scheduler
```

### 3. Verifikuj da radi

#### Test 1: Proveri da li je Command registrovan

```bash
php artisan list | grep auto-logout
```

Očekivani output:
```
users:auto-logout           Automatically logout all authenticated users at specified time
users:auto-logout-overtime  Automatically logout users who did not respond to overtime presence check
```

#### Test 2: Ručno pokreni Command

```bash
php artisan users:auto-logout-overtime
```

Očekivani output:
```
Checking for users who need overtime auto-logout...
No users need overtime auto-logout.
```

Ili ako ima korisnika za logout:
```
Checking for users who need overtime auto-logout...
User 123 (John Doe): prompt shown 12 min ago, timeout is 10 min
✓ Logged out user: John Doe (ID: 123)
Overtime auto-logout completed. Logged out 1 user(s).
```

#### Test 3: Proveri Scheduler

```bash
php artisan schedule:list
```

Trebalo bi da vidiš:
```
0 * * * *  php artisan users:auto-logout ......... Next Due: 1 hour from now
* * * * *  php artisan users:auto-logout-overtime  Next Due: 1 minute from now
* * * * *  php artisan emails:send-reminders ..... Next Due: 1 minute from now
```

#### Test 4: Prati Log

Otvori log i prati šta se dešava:

```bash
tail -f storage/logs/laravel.log
```

Kada Command se izvrši, videćeš:
```
[timestamp] local.INFO: Overtime auto-logout: Processing user 123 (John Doe)
[timestamp] local.INFO: Overtime auto-logout: TimeLog 456 closed for user 123
[timestamp] local.INFO: Overtime auto-logout: Deleted 2 sessions for user 123
[timestamp] local.INFO: Overtime auto-logout: 1 user(s) logged out due to no response to overtime prompt
```

## Simuliranje Scenarija "Ugašen Računar"

### Test Scenario:

1. **Prijavi se** kao korisnik u aplikaciji
2. **Sačekaj** da se overtime modal prikaže (nakon `overtime_check_time`)
3. **NE KLIKNI "Da"** - samo zatvori browser ili ugasi računar
4. **Sačekaj** `overtime_prompt_timeout` minuta (npr. 10 minuta)
5. **Proveri** bazu podataka:

```sql
SELECT UserID, FirstName, LastName, Status, overtime_prompt_shown_at, last_activity_at
FROM Users
WHERE UserID = <tvoj_user_id>;
```

Očekivani rezultat nakon timeout-a:
- `Status` = 'Odjavljen' ✅
- `TimeLog` zatvoren sa `RazlogOdjave` = 'Аутоматска одјава...' ✅

6. **Proveri log**:

```bash
grep "Overtime auto-logout" storage/logs/laravel.log | tail -20
```

## Podešavanja

Sva podešavanja su u `Settings` tabeli:

| Setting Key | Default | Opis |
|------------|---------|------|
| `overtime_check_time` | "15:30" | Vreme kada počinje overtime provera |
| `overtime_prompt_timeout` | 10 | Koliko minuta korisnik ima da odgovori (u minutima) |
| `overtime_prompt_interval` | 15 | Koliko često se modal prikazuje ponovo (u minutima) |
| `overtime_working_days` | "Mon,Tue,Wed,Thu,Fri" | Radni dani |

## Dupla Zaštita (Frontend + Backend)

Sada imaš **DVA mehanizma** za auto-logout:

### 1. Frontend (useOvertimeCheck.js)
- Prikazuje modal korisniku
- Ako korisnik ne klikne u roku, poziva `/attendance/overtime/auto-checkout`
- ✅ Radi ako je browser/računar UKLJUČEN

### 2. Backend (AutoLogoutOvertimeUsers Command)
- Izvršava se svakog minuta via scheduler
- Proverava ko nije odgovorio na overtime prompt
- Automatski odjavljuje korisnike čak i ako je računar UGAŠEN
- ✅ Radi UVEK (čak i ako je klijent offline)

## Prednosti

✅ **Garantovan logout** - Čak i ako korisnik ugasi računar, server će ga odjaviti
✅ **Automatski** - Nema potrebe za ručnim intervencijama
✅ **Logovanje** - Sve akcije se loguju u Laravel log
✅ **Bezbednost** - Sessions se brišu, korisnik ne može nastaviti rad
✅ **Transparentnost** - TimeLog ima razlog zašto je korisnik odjavljen

## Troubleshooting

### Command se ne izvršava

**Provera:**
```bash
php artisan schedule:work
```

Ako vidiš:
```
No scheduled commands are ready to run.
```

Znači scheduler radi ali nema task-ova koji treba da se izvrše SADA.

**Rešenje:** Pokreni ručno:
```bash
php artisan users:auto-logout-overtime
```

### Command se izvršava ali ne odjavljuje korisnike

**Provera:**
```bash
php artisan users:auto-logout-overtime -v
```

Prati output. Možda:
- Nije radni dan
- Nije vreme za overtime proveru
- Korisnici su odgovorili na vreme

**Debug:**
```sql
SELECT UserID, FirstName, Status, overtime_prompt_shown_at, last_activity_at
FROM Users
WHERE Status = 'Prijavljen';
```

### Scheduler ne radi u production

**Provera da li cron radi:**
```bash
crontab -l
```

Trebalo bi da vidiš:
```
* * * * * cd /path/to/employee-attendance && php artisan schedule:run >> /dev/null 2>&1
```

**Provera da li se izvršava:**
```bash
grep CRON /var/log/syslog | tail -20
```

## Zaključak

✅ **SADA**: Automatski logout RADI čak i ako je računar ugašen
✅ **Ranije**: Logout je radio samo ako je browser bio otvoren

Server-side Command osigurava da se korisnici UVEK odjave ako ne odgovore na overtime prompt, bez obzira na stanje klijentskog računara.
