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

        // Get paginated users (10 per page) with sector relationship and active log
        $users = $query->with(['sector', 'activeTimeLog'])
            ->orderBy('FirstName')
            ->orderBy('LastName')
            ->paginate(10)
            ->withQueryString();

        // Add current_status and activeTimeLog to each user
        $users->getCollection()->transform(function ($user) {
            $user->current_status = $user->current_status;
            $user->active_time_log = $user->activeTimeLog; // Explicitly add as attribute for Inertia
            return $user;
        });

        // Location filter - filter collection after loading
        if ($request->has('location') && $request->location) {
            $locationFilter = $request->location;
            $users->setCollection(
                $users->getCollection()->filter(function ($user) use ($locationFilter) {
                    // Get user's active log IP address
                    if (!$user->activeTimeLog || !$user->activeTimeLog->IpAdresaPrijave) {
                        return $locationFilter === 'unknown';
                    }

                    $ip = $user->activeTimeLog->IpAdresaPrijave;
                    $isOffice = str_starts_with($ip, '10.');

                    if ($locationFilter === 'office') {
                        return $isOffice;
                    } else if ($locationFilter === 'remote') {
                        return !$isOffice;
                    }

                    return true;
                })
            );
        }

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
                'location' => $request->location,
            ],
        ]);
    }

    /**
     * Get all currently checked-in remote users (for debug modal).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRemoteUsers(Request $request)
    {
        $user = auth()->user();

        // Build query with same permissions as index
        $query = User::query();

        // Sector filter - for Rukovodilac, only show users from same sector
        if ($user->isRukovodilac() && $user->sector_id) {
            $query->where('sector_id', $user->sector_id);
        }

        // Get ALL users (no pagination) with activeTimeLog, only those checked in
        $allUsers = $query->with('activeTimeLog')
            ->where('Status', 'Prijavljen')
            ->get();

        // Add current_status and active_time_log to each user (same as index method)
        $allUsers->transform(function ($u) {
            $u->current_status = $u->current_status;
            $u->active_time_log = $u->activeTimeLog;
            return $u;
        });

        // Filter only remote users
        $remoteUsers = $allUsers->filter(function ($u) {
            if (!$u->activeTimeLog) return false;

            $ip = $u->activeTimeLog->IpAdresaPrijave;
            if (!$ip) return false;

            // Remote if NOT office (10.15.32.x or 172.x)
            $isOffice = str_starts_with($ip, '10.15.32.') || str_starts_with($ip, '172.');
            return !$isOffice;
        })->values();

        return response()->json([
            'success' => true,
            'data' => $remoteUsers,
        ]);
    }
}
