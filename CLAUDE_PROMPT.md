# Debugging Overtime Modal Issue - Detaljan Task

## Problem
Overtime modal se prikazuje prvi put, ali kada korisnik klikne "Da, i dalje sam na poslu", modal se NIKAD više ne prikazuje čak i nakon što prođe postavljeni interval (2 minuta).

Podešavanja u Settings:
- `overtime_prompt_interval` = 2 (minuta)
- `overtime_prompt_timeout` = 2 (minuta)

Takođe, u browser console-u se NE POJAVLJUJE NIKAKAV LOG, što znači da ili:
1. Frontend kod ne izvršava se uopšte
2. Build nije uspeo
3. Composable se ne koristi u komponentama

## Potrebne provere i ispravke

### 1. Proveri da li se useOvertimeCheck composable ZAISTA koristi

**Fajl:** `resources/js/Pages/User/Index.vue`

Proveri:
- Da li se importuje `useOvertimeCheck`?
- Da li se poziva u `setup()` funkciji?
- Da li se modal renderuje u template-u?

**Ako NE**, dodaj:
```vue
<script setup>
import { useOvertimeCheck } from '@/composables/useOvertimeCheck';

const {
    showOvertimePrompt,
    overtimeMessage,
    timeRemaining,
    formatTimeRemaining,
    confirmPresence
} = useOvertimeCheck();
</script>

<template>
    <!-- Na kraju template-a, dodaj overtime modal -->
    <Teleport to="body">
        <div v-if="showOvertimePrompt" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Провера присуства
                </h3>
                <p class="text-gray-700 mb-4">{{ overtimeMessage }}</p>
                <p class="text-sm text-gray-500 mb-6">
                    Преостало време: <span class="font-mono font-bold text-red-600">{{ formatTimeRemaining() }}</span>
                </p>
                <div class="flex justify-end space-x-3">
                    <button
                        @click="confirmPresence"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        Да, и даље сам на послу
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
```

### 2. Proveri da li je frontend build uspeo

```bash
npm run build
```

Ako postoje greške, ispravi ih.

### 3. Proveri Settings u bazi podataka

```bash
php artisan tinker
```

U tinker-u:
```php
DB::table('Settings')->whereIn('SettingKey', ['overtime_prompt_interval', 'overtime_prompt_timeout', 'overtime_check_time'])->get();
```

**Očekivani rezultat:**
- `overtime_prompt_interval` = "2"
- `overtime_prompt_timeout` = "2"
- `overtime_check_time` = "15:30" (ili bilo koje vreme)

Ako nisu 2, postavi ih:
```php
DB::table('Settings')->updateOrInsert(['SettingKey' => 'overtime_prompt_interval'], ['SettingValue' => '2']);
DB::table('Settings')->updateOrInsert(['SettingKey' => 'overtime_prompt_timeout'], ['SettingValue' => '2']);
```

### 4. Proveri overtime kolone u Users tabeli

```php
// U tinker-u
$user = DB::table('Users')->where('UserID', 1)->first(['overtime_prompt_shown_at', 'last_activity_at']);
print_r($user);
```

Trebalo bi da vidiš `overtime_prompt_shown_at` i `last_activity_at` kolone.

### 5. Testiraj logiku direktno - Kreiraj test skript

**Fajl:** `test-overtime-logic.php`

```php
<?php

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== OVERTIME LOGIC TEST ===\n\n";

// Simuliraj scenario
$overtimeCheckTime = DB::table('Settings')->where('SettingKey', 'overtime_check_time')->value('SettingValue') ?? '15:30';
$promptInterval = (int) (DB::table('Settings')->where('SettingKey', 'overtime_prompt_interval')->value('SettingValue') ?? 15);

$currentTime = Carbon::now();
$lastPromptAt = Carbon::now()->subMinutes(3); // Simuliraj 3 minuta od poslednjeg prompta

echo "Current time: {$currentTime->format('Y-m-d H:i:s')}\n";
echo "Last prompt at: {$lastPromptAt->format('Y-m-d H:i:s')}\n";
echo "Prompt interval: {$promptInterval} minuta\n";
echo "Minutes passed: " . $currentTime->diffInMinutes($lastPromptAt) . "\n\n";

if ($currentTime->diffInMinutes($lastPromptAt) < $promptInterval) {
    echo "❌ Modal se NEĆE prikazati (interval nije prošao)\n";
} else {
    echo "✅ Modal BI TREBALO da se prikaže (interval JE prošao)\n";
}

// Proveri stvarne vrednosti za prijavljene korisnike
echo "\n=== PRIJAVLJENI KORISNICI ===\n";
$users = DB::table('Users')
    ->where('Status', 'Prijavljen')
    ->get(['UserID', 'FirstName', 'LastName', 'overtime_prompt_shown_at', 'last_activity_at']);

foreach ($users as $user) {
    echo "\nUser: {$user->FirstName} {$user->LastName}\n";
    echo "  overtime_prompt_shown_at: {$user->overtime_prompt_shown_at}\n";
    echo "  last_activity_at: {$user->last_activity_at}\n";

    if ($user->overtime_prompt_shown_at) {
        $lastPrompt = Carbon::parse($user->overtime_prompt_shown_at);
        $minutesPassed = Carbon::now()->diffInMinutes($lastPrompt);
        echo "  Minutes passed: {$minutesPassed}\n";
        echo "  Should show modal: " . ($minutesPassed >= $promptInterval ? 'YES' : 'NO') . "\n";
    }
}
```

Pokreni:
```bash
php test-overtime-logic.php
```

### 6. Proveri Network requests u Browser Developer Tools

Otvori DevTools (F12) → Network tab:
- Da li se poziva `/attendance/overtime/check` svakih 60 sekundi?
- Kakav je response?
- Da li postoje greške (401, 403, 500)?

### 7. Proveri Laravel logs

```bash
tail -f storage/logs/laravel.log
```

Trebalo bi da vidiš:
```
[timestamp] local.INFO: Overtime check: interval not passed
[timestamp] local.INFO: Overtime check: showing prompt
[timestamp] local.INFO: Overtime presence confirmed
```

Ako NE VIDIŠ ove logove, znači da endpoint `/attendance/overtime/check` se UOPŠTE NE POZIVA!

### 8. Proveri routes

```bash
php artisan route:list | grep overtime
```

Trebalo bi da vidiš:
```
POST    /attendance/overtime/confirm
POST    /attendance/overtime/auto-checkout
GET     /attendance/overtime/check
```

Ako ne vidiš ove rute, dodaj u `routes/web.php`:
```php
Route::middleware(['auth'])->group(function () {
    Route::get('/attendance/overtime/check', [AttendanceController::class, 'checkOvertimeStatus'])->name('attendance.overtime.check');
    Route::post('/attendance/overtime/confirm', [AttendanceController::class, 'confirmOvertimePresence'])->name('attendance.overtime.confirm');
    Route::post('/attendance/overtime/auto-checkout', [AttendanceController::class, 'autoCheckoutOvertime'])->name('attendance.overtime.auto-checkout');
});
```

### 9. Hardcode test u frontend-u

Da proveriš da li se uopšte izvršava composable, dodaj u `useOvertimeCheck.js` na početku `onMounted`:

```javascript
onMounted(() => {
    console.log('🔴 useOvertimeCheck MOUNTED! Composable se izvršava.');

    // Proveri odmah
    checkOvertimeStatus();

    // Pa onda svakih 60 sekundi
    checkInterval = setInterval(checkOvertimeStatus, 60000);
});
```

Ako NE VIDIŠ "🔴 useOvertimeCheck MOUNTED!" u console-u, znači composable se NE KORISTI u komponenti!

### 10. Finalna provera - Force modal test

Dodaj privremeni test button u `User/Index.vue`:

```vue
<button @click="testModal" class="bg-red-500 text-white px-4 py-2 rounded">
    TEST MODAL
</button>

<script setup>
const testModal = () => {
    console.log('Testing modal...');
    showOvertimePrompt.value = true;
    overtimeMessage.value = 'TEST MESSAGE';
    promptTimeout.value = 2;
    timeRemaining.value = 120;
};
</script>
```

Klikni na button - da li se modal pojavljuje?
- **Ako DA**: Problem je u backend logici ili API pozivu
- **Ako NE**: Problem je u frontend komponenti ili modal nije implementiran

## Očekivani ishod

Nakon svih provera i ispravki:
1. U browser console-u ćeš videti logove svakih 60 sekundi
2. U Laravel logu (`storage/logs/laravel.log`) ćeš videti overtime check logove
3. Modal će se ponovo pojaviti nakon 2 minuta od potvrde

## Ako ništa ne radi

Ako ni posle svega modal ne radi, vratite report sa:
1. Screenshot Network tab-a (da li se poziva `/attendance/overtime/check`)
2. Screenshot Console tab-a (da li ima logova)
3. Output od `test-overtime-logic.php`
4. Sadržaj `storage/logs/laravel.log` (poslednje linije)
