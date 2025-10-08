<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\TimeLog;
use App\Models\Setting;
use App\Models\Reason;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
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
