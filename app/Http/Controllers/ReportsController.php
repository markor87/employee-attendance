<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Sector;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportsController extends Controller
{
    /**
     * Display reports dashboard with statistics and sector-filtered users.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Build base query for statistics (filtered by sector for Rukovodilac)
        $statsQuery = User::query();
        if ($user->isRukovodilac() && $user->sector_id) {
            $statsQuery->where('sector_id', $user->sector_id);
        }

        // Get statistics (filtered for Rukovodilac, all for Admin/SuperAdmin)
        $totalUsers = (clone $statsQuery)->count();
        $checkedIn = (clone $statsQuery)->where('Status', 'Prijavljen')->count();

        // Calculate users on leave (Службено одсуство)
        $onLeave = (clone $statsQuery)->get()->filter(function($u) {
            return $u->current_status === 'Службено одсуство';
        })->count();

        // Build users query for table
        $query = User::query();

        // Sector filter - for Rukovodilac, only show users from same sector
        if ($user->isRukovodilac() && $user->sector_id) {
            $query->where('sector_id', $user->sector_id);
        } else {
            // For other roles, allow sector filter from request
            if ($request->has('sector') && $request->sector !== '' && $request->sector !== null) {
                $query->where('sector_id', $request->sector);
            }
        }

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('FirstName', 'like', "%{$search}%")
                  ->orWhere('LastName', 'like', "%{$search}%")
                  ->orWhere('Email', 'like', "%{$search}%");
            });
        }

        // Get paginated users (10 per page) with sector relationship
        $users = $query->with('sector')
            ->orderBy('FirstName')
            ->orderBy('LastName')
            ->paginate(10)
            ->withQueryString();

        // Add current_status to each user
        $users->getCollection()->transform(function ($user) {
            $user->current_status = $user->current_status;
            return $user;
        });

        // Get all sectors for filter dropdown (only for non-Rukovodilac users)
        $sectors = Sector::orderBy('sector', 'asc')->get();

        return Inertia::render('Reports/Index', [
            'user' => [
                'UserID' => $user->UserID,
                'FirstName' => $user->FirstName,
                'LastName' => $user->LastName,
                'Email' => $user->Email,
                'Role' => $user->Role,
                'Status' => $user->Status,
                'sector_id' => $user->sector_id,
                'isAdmin' => $user->isAdmin(),
                'isRukovodilac' => $user->isRukovodilac(),
            ],
            'stats' => [
                'total_users' => $totalUsers,
                'checked_in' => $checkedIn,
                'on_leave' => $onLeave,
            ],
            'users' => $users,
            'sectors' => $sectors,
            'filters' => [
                'search' => $request->search,
                'sector' => $request->sector,
            ],
        ]);
    }
}
