# Production Security Checklist

## Pre-Deployment Provere

### 1. Environment Konfiguracija (.env)

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` generisan (php artisan key:generate)
- [ ] `APP_URL` postavljen na production URL (https://...)

### 2. Session & Cookie Security

- [ ] `SESSION_DRIVER=database` (preporučeno za production)
- [ ] `SESSION_LIFETIME=60` (ili kraće, zavisno od potreba)
- [ ] `SESSION_ENCRYPT=true`
- [ ] `SESSION_SECURE_COOKIE=true` (OBAVEZNO za HTTPS)
- [ ] `SESSION_HTTP_ONLY=true`
- [ ] `SESSION_SAME_SITE=lax`

### 3. Database Security

- [ ] Snažna database lozinka (kompleksna, najmanje 16 karaktera)
- [ ] Database korisnik sa ograničenim privilegijama
- [ ] SSL/TLS konekcija sa bazom (ako je remote)
- [ ] Regular backup plan implementiran
- [ ] Enkriptovan backup storage

### 4. HTTPS & SSL/TLS

- [ ] SSL sertifikat instaliran (Let's Encrypt ili komercijalni)
- [ ] HTTPS forsiran na celoj aplikaciji
- [ ] HSTS header omogućen (SecurityHeaders middleware)
- [ ] HTTP -> HTTPS redirekcija podešena na web server nivou

### 5. Security Headers

- [ ] SecurityHeaders middleware omogućen u `bootstrap/app.php`
- [ ] CSP (Content-Security-Policy) prilagođen za produkciju
- [ ] Testirati da CSP ne blokira legitiman sadržaj

### 6. Password & Authentication

- [ ] Password strength validacija aktiv na (StrongPassword rule)
- [ ] Brute force zaštita aktivna (LoginAttemptService)
- [ ] 2FA obavezan za admin naloge
- [ ] Rate limiting podešen na login/2FA endpointe

### 7. CSRF Protection

- [ ] CSRF middleware omogućen
- [ ] Svi POST/PUT/DELETE/PATCH zahtevi zaštićeni

### 8. Logging & Monitoring

- [ ] Log level postavljen na `warning` ili `error` u produkciji
- [ ] Osetljivi podaci maskirani u logovima (email, IP)
- [ ] Log monitoring sistem podešen (opciono)
- [ ] Failed login attempts se prate i alarmiraju

### 9. File Permissions

**Linux/Unix serveri:**
```bash
# Storage i cache direktorijumi
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Ostali fajlovi
chmod -R 755 .
chmod 600 .env
```

### 10. Web Server Konfiguracija

**Nginx preporuke:**
- [ ] Disable server signatures
- [ ] Rate limiting podešen
- [ ] Request size limit podešen
- [ ] Timeout konfigurisan

**Apache preporuke:**
- [ ] ServerTokens Prod
- [ ] ServerSignature Off
- [ ] mod_security instaliran (opciono)

### 11. Dependencies & Updates

- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm run build` (production build)
- [ ] SVE zavisnosti ažurirane na najnovije stabilne verzije
- [ ] Security vulnerabilities proverene (`composer audit`)

### 12. Email Configuration

- [ ] SMTP kredencijali bezbedno skladišteni
- [ ] Email rate limiting podešen
- [ ] SPF, DKIM, DMARC podešeni za domen

### 13. Backup & Recovery

- [ ] Automatski database backup (dnevno)
- [ ] Automatski file backup (nedeljno)
- [ ] Backup retention policy definisan
- [ ] Recovery plan testiran

### 14. Firewall & Network Security

- [ ] Firewall pravila podešena (samo 80/443 otvoreni za web)
- [ ] SSH pristup ograničen (key-based auth, disable root login)
- [ ] Fail2Ban ili sličan sistem instaliran
- [ ] DDoS zaštita aktivna (CloudFlare, server-level)

### 15. Application-Specific

- [ ] Test nalozi obrisani iz produkcione baze
- [ ] Debug routes onemogućeni (`/test-db`, `/dashboard-test`)
- [ ] Admin dashboard zaštićen dodatnim merama
- [ ] IP whitelist za kritične admin operacije (opciono)

### 16. Compliance & Legal

- [ ] GDPR compliance proveren (ako je relevantno)
- [ ] Privacy policy dokumentovan
- [ ] Data retention policy definisan
- [ ] User data export/delete funkcionalnost (ako je potrebno)

### 17. Monitoring & Alerting

- [ ] Uptime monitoring podešen
- [ ] Error alerting podešen
- [ ] Performance monitoring (opciono)
- [ ] Security event alerting

### 18. Post-Deployment

- [ ] Security scan izvršen (npr. OWASP ZAP, Burp Suite)
- [ ] Penetration testing izvršen (opciono)
- [ ] Load testing izvršen
- [ ] Disaster recovery plan dokumentovan

---

## Redovne Sigurnosne Provere (Mesečno)

- [ ] Review failed login attempts
- [ ] Review audit logs
- [ ] Check for dependency updates (`composer outdated`)
- [ ] Review security advisories
- [ ] Verify backup integrity
- [ ] Review user permissions

---

## Emergency Contacts

**System Administrator:**
- Name: _______________
- Phone: _______________
- Email: _______________

**Security Contact:**
- Name: _______________
- Phone: _______________
- Email: _______________

**Hosting Provider Support:**
- Phone: _______________
- Email: _______________
- Portal: _______________

---

## Version History

| Date | Version | Changes | Author |
|------|---------|---------|--------|
| 2025-01-XX | 1.0 | Initial security hardening | Claude Code |
