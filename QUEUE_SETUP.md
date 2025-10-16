# Queue Sistem - Dokumentacija

## Šta je Queue i zašto ga koristimo?

Queue (red čekanja) sistem omogućava **asinhrono izvršavanje zadataka** u pozadini, što poboljšava performanse aplikacije i korisničko iskustvo.

### Prednosti Queue sistema:

✅ **Bolje performanse** - Emailovi se šalju u pozadini, ne blokiraju aplikaciju
✅ **Automatski retry** - Ako email ne uspe, sistem automatski pokušava ponovo (3× sa 60s pauzom)
✅ **Skalabilnost** - Lako se skalira na stotine ili hiljade emailova
✅ **Tracking** - Možeš videti koji poslovi su uspeli, pending, ili failed
✅ **Paralelno izvršavanje** - Može slati više emailova istovremeno

---

## 📋 Preduslovi

Proveri da li su kreirane potrebne tabele u bazi:

```sql
-- Jobs tabela (za Queue poslove)
CREATE TABLE `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  INDEX `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed Jobs tabela (za praćenje neuspelih poslova)
CREATE TABLE `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(255) NOT NULL UNIQUE,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## ⚙️ Konfiguracija

### 1. Proveri .env fajl

Otvori `.env` i proveri da li je:

```env
QUEUE_CONNECTION=database
```

- `database` = asinhrono (produkcija)
- `sync` = sinhrono (samo za testing)

### 2. Email podešavanja (SMTP)

Proveri da su SMTP kredencijali pravilno podešeni:

```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Employee Attendance System"
```

---

## 🚀 Pokretanje Queue Worker-a

Queue Worker je proces koji **kontinuirano čeka i izvršava poslove iz Queue-a**.

### Development (lokalno testiranje):

```bash
php artisan queue:work
```

**Napomena:** Worker radi dok ga ne zaustaviš (Ctrl+C).

### Production (sa auto-restart):

```bash
php artisan queue:work --tries=3 --timeout=60
```

Opcije:
- `--tries=3` - Pokušaj 3× pre nego što označiš posao kao failed
- `--timeout=60` - Maksimalno 60 sekundi po poslu

---

## 🔄 Kako radi slanje reminder emailova?

### 1. Cron Job pokreće komandu:

```bash
* * * * * php artisan emails:send-reminders
```

### 2. Komanda stavlja poslove u Queue:

```
SendReminderEmails komanda:
  ↓
  Nađe korisnike sa Status='Odjavljen' (check-in) ili 'Prijavljen' (check-out)
  ↓
  Za svakog korisnika: SendReminderEmail::dispatch()
  ↓
  Poslovi se dodaju u `jobs` tabelu
  ↓
  Komanda se završi ODMAH (ne čeka slanje)
```

### 3. Queue Worker obrađuje poslove:

```
Queue Worker (php artisan queue:work):
  ↓
  Uzme prvi posao iz `jobs` tabele
  ↓
  Izvršava SendReminderEmail Job → šalje email
  ↓
  Ako uspe: obriše posao iz `jobs` tabele
  ↓
  Ako ne uspe: pokuša ponovo (max 3×, sa 60s pauzom)
  ↓
  Ako 3× ne uspe: premesti u `failed_jobs` tabelu
```

---

## 📊 Monitoring Queue-a

### Proveri pending poslove (čekaju izvršenje):

```bash
php artisan queue:monitor
```

Ili direktno u bazi:

```sql
SELECT * FROM jobs;
```

### Proveri failed poslove:

```bash
php artisan queue:failed
```

Ili direktno u bazi:

```sql
SELECT * FROM failed_jobs;
```

### Retry failed poslova:

```bash
# Retry svih failed poslova
php artisan queue:retry all

# Retry specifičnog posla po ID-u
php artisan queue:retry 5
```

### Obriši failed poslove:

```bash
# Obriši sve failed poslove
php artisan queue:flush

# Obriši specifičan failed posao
php artisan queue:forget 5
```

---

## 🛠️ Production Setup - Windows Server

### Opcija 1: Task Scheduler (preporučeno za Windows)

1. **Otvori Task Scheduler**
2. **Create Basic Task**:
   - Name: `Queue Worker - Employee Attendance`
   - Trigger: `When the computer starts`
   - Action: `Start a program`
   - Program: `C:\php\php.exe` (putanja do PHP executable)
   - Arguments: `artisan queue:work --tries=3 --timeout=60`
   - Start in: `C:\path\to\employee-attendance` (putanja do projekta)
3. **Podešavanja**:
   - ✅ Run whether user is logged on or not
   - ✅ Run with highest privileges
   - ✅ If the task fails, restart every 1 minute

### Opcija 2: NSSM (Non-Sucking Service Manager)

NSSM omogućava da Queue Worker radi kao Windows Service:

```bash
# Download NSSM: https://nssm.cc/download

# Install service
nssm install QueueWorker "C:\php\php.exe" "artisan queue:work --tries=3 --timeout=60"
nssm set QueueWorker AppDirectory "C:\path\to\employee-attendance"

# Start service
nssm start QueueWorker
```

### Opcija 3: Supervisor (za Linux)

Ako koristiš Linux server, koristi Supervisor:

```ini
[program:employee-attendance-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/employee-attendance/artisan queue:work --tries=3 --timeout=60
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/queue-worker.log
stopwaitsecs=3600
```

Aktiviraj:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start employee-attendance-worker:*
```

---

## 🧪 Testiranje Queue Sistema

### 1. Test sa sinhronim izvršavanjem:

```bash
# Postavi sync u .env (privremeno)
QUEUE_CONNECTION=sync

# Pokreni komandu (emailovi se šalju ODMAH)
php artisan emails:send-reminders
```

### 2. Test sa Queue-om:

```bash
# Vrati database u .env
QUEUE_CONNECTION=database

# Pokreni komandu (poslovi se dodaju u Queue)
php artisan emails:send-reminders

# Proveri jobs tabelu
php artisan tinker
>>> DB::table('jobs')->count()

# Pokreni Worker (izvršava poslove)
php artisan queue:work

# Proveri logs
tail -f storage/logs/laravel.log
```

---

## 📝 Log Fajlovi

### Lokacija:

```
storage/logs/laravel.log
```

### Šta se loguje:

#### Kada se komanda pokrene:

```
[INFO] check-in reminders: 3 jobs dispatched to Queue at 07:25
```

#### Kada Worker šalje email:

```
[INFO] Queue: Sending check-in reminder to: marko@example.com
[INFO] Queue: Successfully sent check-in reminder to: marko@example.com
```

#### Ako email ne uspe:

```
[ERROR] Queue: Failed to send check-in reminder to marko@example.com: SMTP Error...
```

#### Ako posao trajno ne uspe nakon 3 pokušaja:

```
[ERROR] Queue: Job permanently failed for marko@example.com after 3 attempts: SMTP Error...
```

---

## ❓ Troubleshooting

### Problem: Worker se ne pokreće

**Proveri:**
```bash
# Proveri PHP verziju (min 8.2)
php -v

# Proveri ekstenzije
php -m | findstr pdo_mysql
```

### Problem: Poslovi ostaju u `jobs` tabeli

**Uzrok:** Worker nije pokrenut ili je pao.

**Rešenje:**
```bash
# Pokreni Worker ponovo
php artisan queue:work
```

### Problem: Svi poslovi završavaju u `failed_jobs`

**Uzrok:** SMTP kredencijali nisu validni ili server nije dostupan.

**Proveri:**
```bash
# Test SMTP konekcije
php artisan tinker
>>> Mail::raw('Test', function($m) { $m->to('test@example.com')->subject('Test'); })
```

### Problem: Worker koristi puno memorije

**Rešenje:** Dodaj memory limit i restartuj periodično:

```bash
php artisan queue:work --memory=128 --timeout=60
```

Ili konfiguriši Task Scheduler/Supervisor da restartuje Worker svakih par sati.

---

## 📚 Dodatni Resursi

- [Laravel Queue Dokumentacija](https://laravel.com/docs/11.x/queues)
- [Laravel Horizon](https://laravel.com/docs/11.x/horizon) - Napredni Queue dashboard (za Redis)
- [NSSM Download](https://nssm.cc/download)

---

## 🎯 Sledeći Koraci

1. ✅ Kreiraj `jobs` i `failed_jobs` tabele u bazi
2. ✅ Postavi `QUEUE_CONNECTION=database` u .env
3. ✅ Testiraj Queue lokalno: `php artisan queue:work`
4. ✅ Konfiguriši Task Scheduler ili NSSM za produkciju
5. ✅ Nadgledaj `failed_jobs` tabelu redovno

---

**Pitanja?** Proveri `storage/logs/laravel.log` za detaljne informacije o izvršavanju.
