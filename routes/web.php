<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingsController;
use App\Models\User;
use App\Models\TimeLog;
use App\Models\Setting;
use App\Models\Reason;
use Illuminate\Support\Facades\DB;

// Guest Routes (not authenticated)
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.show');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    // 2FA
    Route::get('/2fa', [TwoFactorController::class, 'show'])->name('2fa.show');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify');
    Route::post('/2fa/resend', [TwoFactorController::class, 'resend'])->name('2fa.resend');
});

// Attendance Status - Outside auth middleware to return proper 401 when session expired
Route::get('/attendance/status', [AttendanceController::class, 'status'])->name('attendance.status');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/admin-logout', [AuthController::class, 'adminLogout'])->name('admin.logout');

    // Password Management
    Route::get('/change-password', [PasswordController::class, 'showChangeForm'])->name('password.change.show');
    Route::post('/password/change', [PasswordController::class, 'change'])->name('password.change');

    // Force Password Change (PasswordNeedsChange = true)
    Route::get('/force-change-password', [PasswordController::class, 'showForceChangeForm'])->name('password.force.show');
    Route::post('/password/force-change', [PasswordController::class, 'forceChange'])->name('password.force.change');

    // Attendance System
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.checkin');
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.checkout');

    // Force Attendance (Admin/Kadrovik only)
    Route::post('/attendance/force-check-in', [AttendanceController::class, 'forceCheckIn'])->name('attendance.force.checkin');
    Route::post('/attendance/force-check-out', [AttendanceController::class, 'forceCheckOut'])->name('attendance.force.checkout');

    // Logs (User can view own logs, Admin/Kadrovik can view any user's logs)
    Route::get('/logs/{userId}', [LogsController::class, 'show'])->name('logs.show');

    // Admin Logs (Admin/Kadrovik only - view all logs)
    Route::get('/admin/logs', [LogsController::class, 'index'])->name('admin.logs');

    // User Management (SuperAdmin/Admin only)
    Route::middleware('role:SuperAdmin|Admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/force-password-change', [UserController::class, 'forcePasswordChange'])->name('users.forcePasswordChange');
    });

    // Settings (SuperAdmin only)
    Route::middleware('role:SuperAdmin')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-email', [SettingsController::class, 'testEmail'])->name('settings.testEmail');
    });

    // User Dashboard (main dashboard for all users)
    Route::get('/dashboard', function () {
        $user = auth()->user();

        // Get active log (if checked in)
        $activeLog = TimeLog::where('UserID', $user->UserID)
            ->whereNull('VremeOdjave')
            ->latest('VremePrijave')
            ->first();

        // Get reasons for check-in/out
        $checkInReasons = Reason::where('ReasonType', 'Dolazak')->get();
        $checkOutReasons = Reason::where('ReasonType', 'Odlazak')
            ->whereNotIn('ReasonName', ['Аутоматска одјава'])
            ->get();

        // Get today's check-ins for this user
        $todayCheckIns = TimeLog::where('UserID', $user->UserID)
            ->whereDate('VremePrijave', today())
            ->count();

        // Get total logs for this user
        $totalLogs = TimeLog::where('UserID', $user->UserID)->count();

        return inertia('Dashboard/User', [
            'user' => [
                'UserID' => $user->UserID,
                'FirstName' => $user->FirstName,
                'LastName' => $user->LastName,
                'Email' => $user->Email,
                'Role' => $user->Role,
                'Status' => $user->Status,
                'isAdmin' => $user->isAdmin(),
            ],
            'activeLog' => $activeLog,
            'checkInReasons' => $checkInReasons,
            'checkOutReasons' => $checkOutReasons,
            'todayCheckIns' => $todayCheckIns,
            'totalLogs' => $totalLogs,
            'laravelVersion' => app()->version(),
        ]);
    })->name('dashboard');

    // Admin Dashboard (for Admin, Kadrovik, SuperAdmin)
    Route::get('/admin/dashboard', function () {
        // Check if user has admin role
        if (!auth()->user()->isAdmin() && auth()->user()->Role !== 'Kadrovik') {
            abort(403, 'Unauthorized');
        }

        $user = auth()->user();

        // Get statistics
        $totalUsers = User::count();
        $checkedIn = User::where('Status', 'Prijavljen')->count();
        $totalLogs = TimeLog::count();
        $todayCheckins = TimeLog::whereDate('VremePrijave', today())->count();

        // Build users query with search
        $query = User::query();

        // Search filter
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('FirstName', 'like', "%{$search}%")
                  ->orWhere('LastName', 'like', "%{$search}%")
                  ->orWhere('Email', 'like', "%{$search}%");
            });
        }

        // Get paginated users (10 per page)
        $users = $query->orderBy('FirstName')
            ->orderBy('LastName')
            ->paginate(10)
            ->withQueryString();

        return inertia('Dashboard/Admin', [
            'user' => [
                'UserID' => $user->UserID,
                'FirstName' => $user->FirstName,
                'LastName' => $user->LastName,
                'Email' => $user->Email,
                'Role' => $user->Role,
                'Status' => $user->Status,
                'isAdmin' => $user->isAdmin(),
            ],
            'stats' => [
                'total_users' => $totalUsers,
                'checked_in' => $checkedIn,
                'total_logs' => $totalLogs,
                'today_checkins' => $todayCheckins,
            ],
            'users' => $users,
            'filters' => [
                'search' => request('search'),
            ],
            'laravelVersion' => app()->version(),
        ]);
    })->name('admin.dashboard');
});

// Root redirect
Route::get('/', function () {
    return redirect('/login');
});

// Test Dashboard - Inertia.js + Vue 3
Route::get('/dashboard-test', function () {
    $users = User::take(10)->get();
    $totalUsers = User::count();
    $checkedIn = User::where('Status', 'Prijavljen')->count();
    $totalLogs = TimeLog::count();
    $settings = Setting::getAll();

    return \Inertia\Inertia::render('TestDashboard', [
        'users' => $users,
        'stats' => [
            'total_users' => $totalUsers,
            'checked_in' => $checkedIn,
            'total_logs' => $totalLogs,
        ],
        'settings' => $settings,
    ]);
});

// Test route for database connection
Route::get('/test-db', function () {
    try {
        // Test database connection
        DB::connection()->getPdo();
        $dbStatus = '✅ Database connection successful!';

        // Test User model
        $userCount = User::count();
        $users = User::take(3)->get(['UserID', 'FirstName', 'LastName', 'Email', 'Role', 'Status']);

        // Test TimeLog model
        $timeLogCount = TimeLog::count();
        $recentLogs = TimeLog::with('user')->latest('VremePrijave')->take(3)->get();

        // Test Setting model
        $settings = Setting::getAll();

        // Test Reason model
        $reasons = Reason::all();
        $normalReason = Reason::getNormalCheckIn();
        $reasonValid = Reason::validateNormalReason();

        return response()->json([
            'database' => $dbStatus,
            'users' => [
                'count' => $userCount,
                'sample' => $users,
            ],
            'time_logs' => [
                'count' => $timeLogCount,
                'recent' => $recentLogs,
            ],
            'settings' => $settings,
            'reasons' => [
                'all' => $reasons,
                'normal_check_in' => $normalReason,
                'validation_passed' => $reasonValid,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => '❌ Database connection failed!',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});
