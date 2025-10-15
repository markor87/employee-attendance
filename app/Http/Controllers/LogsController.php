<?php

namespace App\Http\Controllers;

use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LogsController extends Controller
{
    /**
     * Show logs for a specific user.
     * Users can view their own logs.
     * Admins/Kadrovik can view any user's logs.
     *
     * @param int $userId
     * @param Request $request
     * @return \Inertia\Response
     */
    public function show(int $userId, Request $request)
    {
        $authUser = Auth::user();

        // Authorization: Users can only view their own logs
        // Admins/Kadrovik can view any user's logs
        if ($authUser->UserID != $userId && !$authUser->isAdmin() && $authUser->Role !== 'Kadrovik') {
            abort(403, 'Немате дозволу да видите ове логове.');
        }

        // Get the user whose logs we're viewing
        $user = User::findOrFail($userId);

        // Build query
        $query = TimeLog::where('UserID', $userId)
            ->with(['user', 'performedByCheckIn', 'performedByCheckOut'])
            ->orderBy('VremePrijave', 'desc');

        // Date range filtering (inclusive end date)
        if ($request->start_date) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $query->where('VremePrijave', '>=', $startDate);
        }

        if ($request->end_date) {
            // CRITICAL: Include entire end day (until 23:59:59)
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->where('VremePrijave', '<=', $endDate);
        }

        // Pagination (15 per page)
        $logs = $query->paginate(15);

        // Calculate statistics for filtered logs
        $statsQuery = TimeLog::where('UserID', $userId)->completed();

        if ($request->start_date) {
            $statsQuery->where('VremePrijave', '>=', Carbon::parse($request->start_date)->startOfDay());
        }

        if ($request->end_date) {
            $statsQuery->where('VremePrijave', '<=', Carbon::parse($request->end_date)->endOfDay());
        }

        $completedLogs = $statsQuery->get();

        // Calculate total hours and days worked
        $totalMinutes = $completedLogs->sum(function ($log) {
            return $log->getDurationInMinutes() ?? 0;
        });

        $totalHours = $totalMinutes / 60;
        $daysWorked = $completedLogs->pluck('RadniDatum')->unique()->count();

        // Get all users for ViewLogModal (to show who performed check-in/check-out)
        $allUsers = User::select('UserID', 'FirstName', 'LastName')
            ->orderBy('FirstName')
            ->orderBy('LastName')
            ->get();

        return inertia('Logs/Index', [
            'user' => [
                'UserID' => $authUser->UserID,
                'FirstName' => $authUser->FirstName,
                'LastName' => $authUser->LastName,
                'Email' => $authUser->Email,
                'Role' => $authUser->Role,
                'Status' => $authUser->Status,
                'isAdmin' => $authUser->isAdmin(),
            ],
            'viewingUser' => [
                'UserID' => $user->UserID,
                'FirstName' => $user->FirstName,
                'LastName' => $user->LastName,
            ],
            'logs' => $logs,
            'statistics' => [
                'total_hours' => round($totalHours, 2),
                'days_worked' => $daysWorked,
                'total_logs' => $completedLogs->count(),
            ],
            'filters' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
            'isOwnLogs' => $authUser->UserID == $userId,
            'allUsers' => $allUsers,
        ]);
    }

    /**
     * Show all users' logs (Admin/Kadrovik only).
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();

        // Authorization: Only Admin/Kadrovik can view all logs
        if (!$authUser->isAdmin() && $authUser->Role !== 'Kadrovik') {
            abort(403, 'Немате дозволу да видите све логове.');
        }

        // Build query
        $query = TimeLog::with(['user', 'performedByCheckIn', 'performedByCheckOut'])
            ->orderBy('VremePrijave', 'desc');

        // User filter
        if ($request->user_id) {
            $query->where('UserID', $request->user_id);
        }

        // Date range filtering (inclusive end date)
        if ($request->start_date) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $query->where('VremePrijave', '>=', $startDate);
        }

        if ($request->end_date) {
            // CRITICAL: Include entire end day (until 23:59:59)
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->where('VremePrijave', '<=', $endDate);
        }

        // Pagination (15 per page)
        $logs = $query->paginate(15);

        // Calculate statistics for all filtered logs
        $statsQuery = TimeLog::completed();

        if ($request->user_id) {
            $statsQuery->where('UserID', $request->user_id);
        }

        if ($request->start_date) {
            $statsQuery->where('VremePrijave', '>=', Carbon::parse($request->start_date)->startOfDay());
        }

        if ($request->end_date) {
            $statsQuery->where('VremePrijave', '<=', Carbon::parse($request->end_date)->endOfDay());
        }

        $completedLogs = $statsQuery->get();

        // Calculate total hours and days worked
        $totalMinutes = $completedLogs->sum(function ($log) {
            return $log->getDurationInMinutes() ?? 0;
        });

        $totalHours = $totalMinutes / 60;
        $daysWorked = $completedLogs->pluck('RadniDatum')->unique()->count();

        // Get all users for filter dropdown
        $allUsers = User::select('UserID', 'FirstName', 'LastName')
            ->orderBy('FirstName')
            ->orderBy('LastName')
            ->get();

        return inertia('Logs/All', [
            'user' => [
                'UserID' => $authUser->UserID,
                'FirstName' => $authUser->FirstName,
                'LastName' => $authUser->LastName,
                'Email' => $authUser->Email,
                'Role' => $authUser->Role,
                'Status' => $authUser->Status,
                'isAdmin' => $authUser->isAdmin(),
            ],
            'logs' => $logs,
            'statistics' => [
                'total_hours' => round($totalHours, 2),
                'days_worked' => $daysWorked,
                'total_logs' => $completedLogs->count(),
            ],
            'filters' => [
                'user_id' => $request->user_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
            'allUsers' => $allUsers,
        ]);
    }
}
