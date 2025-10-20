# Sigurnosna Poboljšanja - Implementacija

## Pregled Implementiranih Mera

Ovaj dokument sadrži sve sigurnosne mere koje su implementirane u aplikaciji za praćenje prisustva zaposlenih.

---

## 1. Security Headers Middleware

**Fajl:** `app/Http/Middleware/SecurityHeaders.php`

**Implementirano:**
- ✅ HSTS (HTTP Strict Transport Security) - **SAMO PRODUCTION**
- ✅ X-Frame-Options (SAMEORIGIN)
- ✅ X-Content-Type-Options (nosniff)
- ✅ X-XSS-Protection - **SAMO PRODUCTION**
- ✅ Referrer-Policy - **SAMO PRODUCTION**
- ✅ Content-Security-Policy (CSP) - **SAMO PRODUCTION**
- ✅ Permissions-Policy - **SAMO PRODUCTION**

**Status:** Omogućeno u `bootstrap/app.php:16`

**VAŽNO - Environment-Aware Konfiguracija:**

### Development Mode (`APP_ENV=local` ili `development`)
```php
// Minimalni headers da bi Vite HMR radio bez problema
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
// CSP je ONEMOGUĆEN u development
```

**Razlog:** Vite dev server koristi IPv6 (`[::1]:5173`) koji nije kompatibilan sa CSP bracket notacijom. Umesto komplikovanog podešavanja, CSP je potpuno onemogućen u development okruženju.

### Production Mode (`APP_ENV=production`)
```php
// SVI security headeri aktivni
- Strict-Transport-Security: max-age=31536000; includeSubDomains
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block
- Referrer-Policy: strict-origin-when-cross-origin
- Content-Security-Policy: stroga politika (detaljno ispod)
- Permissions-Policy: geolocation=(), microphone=(), camera=()
```

**Production CSP Konfiguracija:**
- `default-src 'self'`
- `script-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.bunny.net`
- `style-src 'self' 'unsafe-inline' https://fonts.bunny.net`
- `font-src 'self' https://fonts.bunny.net data:`
- `img-src 'self' data: https: blob:`
- `connect-src 'self'` (bez WebSocket za external sources)
- `frame-ancestors 'self'`
- `base-uri 'self'`
- `form-action 'self'`

**Napomena:** Nakon `npm run build`, produkciona verzija koristi compiled assets (ne Vite dev server), tako da CSP radi bez problema.

---

## 2. Session & Cookie Security

**Fajl:** `.env.example`

**Konfigurirano:**
```env
SESSION_LIFETIME=60          # Smanjeno sa 120 na 60 minuta
SESSION_ENCRYPT=true         # Enkripcija session podataka
SESSION_SECURE_COOKIE=false  # MORA biti true u produkciji
SESSION_HTTP_ONLY=true       # Sprečava JavaScript pristup
SESSION_SAME_SITE=lax        # CSRF zaštita
```

**VAŽNO:** U produkciji OBAVEZNO postaviti:
```env
SESSION_SECURE_COOKIE=true
APP_ENV=production
APP_DEBUG=false
```

---

## 3. CSRF Protection

**Fajlovi:**
- `app/Http/Middleware/VerifyCsrfToken.php`
- `bootstrap/app.php:19-22`

**Implementirano:**
- ✅ CSRF middleware aktivan za sve web rute
- ✅ Token validacija sa `hash_equals()`
- ✅ Exceptions handling za CSRF mismatch

---

## 4. User Enumeration Protection

**Fajl:** `app/Http/Controllers/AuthController.php`

**Izmene:**
- ✅ Generička poruka za sve neuspele loginekontaktpokušaje
- ✅ Ista poruka za nepostojeće i zaključane naloge
- ✅ Poruka: "Неисправан email или лозинка. Покушајте касније."

**Ranije (ranjivo):**
```php
"Налог је привремено закључан..."  // Otkriva da nalog postoji
```

**Sada (bezbedno):**
```php
"Неисправан email или лозинка. Покушајте касније."  // Generička poruka
```

---

## 5. IP-Based Lockout

**Fajl:** `app/Services/LoginAttemptService.php`

**Implementirano:**
- ✅ Dual lockout: po email-u I po IP-u
- ✅ `MAX_ATTEMPTS = 5` (po email-u)
- ✅ `MAX_IP_ATTEMPTS = 10` (po IP-u)
- ✅ `LOCKOUT_MINUTES = 15`

**Metode:**
- `isLockedOut($email, $ipAddress)` - Proverava oba kriterijuma
- `getRecentIpAttempts($ipAddress)` - Broj pokušaja sa IP-a

**Efekat:** Napadač ne može beskonačno da pokušava različite email-ove sa iste IP adrese.

---

## 6. Password Strength Validation

**Fajl:** `app/Rules/StrongPassword.php`

**Zahtevi:**
- ✅ Minimum 8 karaktera
- ✅ Najmanje jedno veliko slovo (A-Z)
- ✅ Najmanje jedno malo slovo (a-z)
- ✅ Najmanje jedan broj (0-9)
- ✅ Najmanje jedan specijalni karakter (!@#$%...)
- ✅ Blacklist slabih lozinki (password123, admin123, itd.)

**Primenjena na:**
- `PasswordController::change()` - Promena lozinke
- `PasswordController::forceChange()` - Forsirana promena
- `UserController::store()` - Kreiranje novog korisnika

---

## 7. Sensitive Data Masking

**Fajl:** `app/Helpers/SecurityHelper.php`

**Metode:**
- `maskEmail($email)` - `john.doe@example.com` → `j***@e***.com`
- `maskIp($ip)` - `192.168.1.100` → `192.168.*.*`
- `hashForLogging($data)` - One-way hash za logove

**Primena:**
- `AuthController::login()` - Maskirani email i IP u error logovima
- `AuditService` - Svi audit logovi koriste maskiranje

---

## 8. Audit Logging

**Fajlovi:**
- `database/migrations/2025_10_20_103949_create_audit_logs_table.php`
- `app/Models/AuditLog.php`
- `app/Services/AuditService.php`

**Tabela `audit_logs`:**
```sql
- id (bigint)
- user_id (bigint, nullable)
- event_type (varchar(50))
- ip_address (varchar(45))
- user_agent (varchar(500))
- description (text)
- metadata (json)
- created_at (timestamp)
```

**Logovani događaji:**
- `failed_login` - Neuspeli login pokušaj
- `successful_login` - Uspešan login
- `logout` - Logout korisnika
- `password_change` - Promena lozinke (sa `forced` flagom)
- `user_created` - Kreiranje novog korisnika
- `user_updated` - Izmena korisnika
- `user_deleted` - Brisanje korisnika
- `2fa_code_sent` - Slanje 2FA koda
- `2fa_verified` - Uspešna 2FA verifikacija
- `2fa_failed` - Neuspela 2FA verifikacija

**Integracija:**
- `AuthController` - Login/logout događaji
- `PasswordController` - Promena lozinke

---

## 9. Migration SQL Fajlovi

Kreirana migracija za audit logging:
```bash
php artisan migrate
```

**Fajl:** `database/migrations/2025_10_20_103949_create_audit_logs_table.php`

---

## 10. Production Security Checklist

**Fajl:** `SECURITY_CHECKLIST.md`

Kompletna checklist sa:
- Pre-deployment provere (18 kategorija)
- Environment konfiguracija
- Database security
- HTTPS/SSL setup
- Firewall & network security
- Backup & recovery plan
- Compliance (GDPR)
- Monitoring & alerting
- Mesečne sigurnosne provere

---

## Kako Testirati

### 1. SecurityHeaders Middleware

**Development testiranje:**
```bash
# Pokreni aplikaciju
php artisan serve

# Proveri headers (u browseru ili curl)
curl -I http://localhost:8000
```

**Očekivani headeri u DEVELOPMENT (`APP_ENV=local`):**
```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
// CSP header NEĆE biti prisutan - ovo je normalno!
```

**Očekivani headeri u PRODUCTION (`APP_ENV=production`):**
```
Strict-Transport-Security: max-age=31536000; includeSubDomains
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.bunny.net; ...
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

**VAŽNO:** Ako vidiš beli ekran u development modu, proveri da li je `APP_ENV=local` u `.env` fajlu.

### 2. Password Strength

Pokušaj da kreiraš korisnika ili promeniš lozinku sa:
- ❌ `password123` - Odbijeno (blacklist)
- ❌ `Test1234` - Odbijeno (bez specijalnog karaktera)
- ✅ `Test1234!` - Prihvaćeno

### 3. IP Lockout

Simuliraj 11 neuspelih pokušaja sa iste IP adrese:
```bash
for i in {1..11}; do
  curl -X POST http://localhost:8000/login \
    -d "email=test$i@example.com&password=wrong"
done
```

Nakon 10 pokušaja, dalji pokušaji će biti blokirani.

### 4. Audit Logs

```bash
# Pokreni migraciju
php artisan migrate

# Nakon login/logout proveri tabelu
php artisan tinker
>>> \App\Models\AuditLog::latest()->take(5)->get()
```

---

## Deployment Uputstvo

### 1. Pre Deploya

```bash
# Instaliraj production dependencies
composer install --no-dev --optimize-autoloader

# Build frontend
npm run build

# Proveri security vulnerabilities
composer audit
```

### 2. Environment Setup

```bash
# Kopiraj .env.example u .env
cp .env.example .env

# Generiši APP_KEY
php artisan key:generate

# Ažuriraj .env sa production vrednostima
```

**Obavezne izmene u .env:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vašdomen.com

SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true

DB_CONNECTION=mysql  # ili vaša baza
DB_HOST=localhost
DB_DATABASE=production_db
DB_USERNAME=secure_user
DB_PASSWORD=JAKO_KOMPLEKSNA_LOZINKA_16+

MAIL_MAILER=smtp  # konfiguriši email
```

### 3. Pokreni Migracije

```bash
php artisan migrate --force
```

### 4. Web Server Konfiguracija

**Nginx primer:**
```nginx
server {
    listen 443 ssl http2;
    server_name vašdomen.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    root /path/to/employee-attendance/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}

# HTTP -> HTTPS redirect
server {
    listen 80;
    server_name vašdomen.com;
    return 301 https://$host$request_uri;
}
```

### 5. File Permissions

```bash
chmod -R 755 /path/to/employee-attendance
chmod -R 775 storage bootstrap/cache
chmod 600 .env
chown -R www-data:www-data storage bootstrap/cache
```

### 6. Post-Deployment Provera

Koristi `SECURITY_CHECKLIST.md` za kompletnu proveru.

---

## Dodatne Preporuke

1. **Fail2Ban** - Instalirati na server nivou za dodatnu zaštitu
2. **CloudFlare** - Koristi WAF i DDoS zaštitu
3. **Backup** - Automatski dnevni backup baze
4. **Monitoring** - Sentry, LogRocket ili slično za error tracking
5. **SSL Certificate** - Let's Encrypt ili komercijalni
6. **Database Encryption** - Enkriptovati osetljive kolone u bazi

---

## Troubleshooting

### Problem: Beli ekran nakon omogućavanja SecurityHeaders

**Simptomi:**
- Aplikacija prikazuje prazan beli ekran
- Browser Console pokazuje CSP greške
- Greška: "Refused to load the script... violates Content Security Policy"

**Rešenje:**
1. Proveri `.env` fajl:
   ```env
   APP_ENV=local  # MORA biti 'local' ili 'development' za dev mode
   ```

2. Ako je `APP_ENV=production`, promeni u `local`:
   ```bash
   # U .env fajlu
   APP_ENV=local
   APP_DEBUG=true
   ```

3. Restartuj server:
   ```bash
   php artisan serve
   ```

4. Clear browser cache (Ctrl+F5 / Cmd+Shift+R)

**Objašnjenje:**
- SecurityHeaders middleware detektuje environment
- U `local`/`development`: CSP je onemogućen (Vite HMR radi)
- U `production`: Svi security headeri aktivni

### Problem: Vite IPv6 Greška

**Simptomi:**
- Greška: "http://[::1]:5173" u console
- CSP blokira IPv6 adrese

**Rešenje:**
CSP ne podržava IPv6 bracket notaciju. SecurityHeaders middleware automatski onemogućava CSP u development modu, tako da ovaj problem ne bi trebao da se desi. Ako se ipak desi:

1. Proveri da je `APP_ENV=local`
2. Alternativno, forsiraj Vite na IPv4:
   ```bash
   # vite.config.js
   server: {
       host: '127.0.0.1',  // Force IPv4
   }
   ```

---

## Support

Za pitanja o sigurnosnim implementacijama, kontaktirajte system administratora ili proverite:
- `SECURITY_CHECKLIST.md` - Production checklist
- `README.md` - Opšta dokumentacija

---

**Verzija:** 1.1
**Datum:** 2025-10-20
**Autor:** Claude Code
**Status:** ✅ Implementirano i testirano (Vite HMR kompatibilno)
