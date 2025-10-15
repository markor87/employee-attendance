# Миграције базе података

Овај директоријум садржи све миграције за Employee Attendance систем.

## Структура миграција

Миграције су креиране у правом редоследу респектујући foreign key зависности:

1. **2024_01_01_000001_create_settings_table.php**
   - Табела за подешавања апликације

2. **2024_01_01_000002_create_cache_table.php**
   - Laravel cache табела

3. **2024_01_01_000003_create_sessions_table.php**
   - Laravel session табела

4. **2024_01_01_000004_create_sectors_table.php**
   - Табела за секторе (одељења)

5. **2024_01_01_000005_create_reasons_table.php**
   - Табела за разлоге пријаве/одјаве

6. **2024_01_01_000006_create_users_table.php**
   - Табела корисника (зависи од sectors)

7. **2024_01_01_000007_create_time_logs_table.php**
   - Табела евиденције присуства (зависи од Users)

8. **2024_01_01_000008_create_failed_login_attempts_table.php**
   - Табела за праћење неуспелих покушаја пријављивања

## Покретање миграција

### Све миграције одједном:
```bash
php artisan migrate
```

### Враћање свих миграција:
```bash
php artisan migrate:rollback
```

### Refresh (drop + migrate):
```bash
php artisan migrate:refresh
```

### Refresh са seeders:
```bash
php artisan migrate:refresh --seed
```

## Важне напомене

### Foreign Keys:
- `Users.sector_id` → `sectors.id` (SET NULL on delete)
- `TimeLogs.UserID` → `Users.UserID` (CASCADE on delete)

### Енумови:
- `Users.Role`: SuperAdmin, Admin, Zaposleni, Kadrovik, Rukovodilac
- `Users.Status`: Prijavljen, Odjavljen
- `reasons.ReasonType`: Dolazak, Odlazak

### Индекси:
- `Users.Email` - UNIQUE
- `sessions.user_id` - INDEX
- `sessions.last_activity` - INDEX
- `failed_login_attempts.email` - INDEX
- `failed_login_attempts.[email, attempted_at]` - COMPOSITE INDEX

### Timestamp колоне:
- `DateCreated` - default CURRENT_TIMESTAMP
- `DateUpdated` - default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
- `attempted_at` - default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

## Миграција са постојеће базе

Ако већ имате базу података и желите да увезете структуру:

1. **Направите backup** постојеће базе
2. **Покрените миграције** на новој/празној бази
3. **Увезите податке** из старе базе (само INSERT упите)

```bash
# Export само података (без структуре)
mysqldump -u root -p --no-create-info --complete-insert employee_attendance > data.sql

# Import података у нову базу
mysql -u root -p employee_attendance_new < data.sql
```

## Разлике између MySQL и SQLite

Ове миграције су компатибилне са оба database engine-а. Laravel аутоматски прилагођава SQL упите.

### MySQL специфично:
- `ON UPDATE CURRENT_TIMESTAMP` - аутоматски ажурира timestamp
- Foreign keys са `CASCADE` и `SET NULL`
- `ENUM` типови

### SQLite специфично:
- Нема native ENUM подршку (Laravel користи VARCHAR са валидацијом)
- Foreign keys морају бити експлицитно омогућени

## Troubleshooting

### Грешка: "SQLSTATE[HY000]: General error: 1 table already exists"
```bash
php artisan migrate:rollback
php artisan migrate
```

### Грешка: "Foreign key constraint fails"
Проверите редослед миграција - parent табеле морају бити креиране пре child табела.

### Грешка: "Syntax error near 'ON UPDATE'"
SQLite не подржава `ON UPDATE CURRENT_TIMESTAMP` на исти начин као MySQL. Laravel ће аутоматски користити triggers за SQLite.
