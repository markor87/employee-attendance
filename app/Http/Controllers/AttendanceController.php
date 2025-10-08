<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Check in user (start work).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkIn(Request $request)
    {
        $user = Auth::user();

        // Validate user is not already checked in
        if ($user->Status === 'Prijavljen') {
            return response()->json([
                'success' => false,
                'message' => 'Већ сте пријављени на посао.',
            ], 400);
        }

        // Validate input
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            // Create new TimeLog entry
            $timeLog = TimeLog::create([
                'UserID' => $user->UserID,
                'VremePrijave' => now(),
                'VremeOdjave' => null,
                'RadniDatum' => now()->toDateString(),
                'IpAdresaPrijave' => $request->ip() ?: 'N/A',
                'IpAdresaOdjave' => null,
                'RazlogPrijave' => $validated['reason'],
                'RazlogOdjave' => null,
                'PerformedByPrijava' => $user->UserID,
                'PerformedByOdjava' => null,
                'Napomena' => $validated['notes'] ?? null,
            ]);

            // Update user status
            $user->update(['Status' => 'Prijavljen']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Успешно сте се пријавили на посао.',
                'data' => [
                    'log' => $timeLog,
                    'status' => 'Prijavljen',
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Дошло је до грешке приликом пријављивања.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check out user (end work).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkOut(Request $request)
    {
        $user = Auth::user();

        // Validate user is checked in
        if ($user->Status === 'Odjavljen') {
            return response()->json([
                'success' => false,
                'message' => 'Нисте пријављени на посао.',
            ], 400);
        }

        // Find active (open) time log for this user
        $timeLog = TimeLog::where('UserID', $user->UserID)
            ->whereNull('VremeOdjave')
            ->latest('VremePrijave')
            ->first();

        if (!$timeLog) {
            return response()->json([
                'success' => false,
                'message' => 'Није пронађен активан лог пријаве.',
            ], 400);
        }

        // Validate input
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            // Combine notes if check-out note provided
            $combinedNotes = $timeLog->Napomena;
            if (isset($validated['notes']) && $validated['notes']) {
                $combinedNotes = $combinedNotes
                    ? $combinedNotes . ';' . $validated['notes']
                    : $validated['notes'];
            }

            // Update time log
            $timeLog->update([
                'VremeOdjave' => now(),
                'IpAdresaOdjave' => $request->ip() ?: 'N/A',
                'RazlogOdjave' => $validated['reason'],
                'PerformedByOdjava' => $user->UserID,
                'Napomena' => $combinedNotes,
            ]);

            // Update user status
            $user->update(['Status' => 'Odjavljen']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Успешно сте се одјавили са посла.',
                'data' => [
                    'log' => $timeLog,
                    'status' => 'Odjavljen',
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Дошло је до грешке приликом одјављивања.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current attendance status for authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function status()
    {
        $user = Auth::user();

        // Get active log if exists
        $activeLog = TimeLog::where('UserID', $user->UserID)
            ->whereNull('VremeOdjave')
            ->latest('VremePrijave')
            ->first();

        return response()->json([
            'status' => $user->Status,
            'isCheckedIn' => $user->Status === 'Prijavljen',
            'activeLog' => $activeLog,
        ]);
    }

    /**
     * Force check-in user (Admin/Kadrovik only).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceCheckIn(Request $request)
    {
        $admin = Auth::user();

        // Validate admin role
        if (!$admin->isAdmin() && $admin->Role !== 'Kadrovik') {
            return response()->json([
                'success' => false,
                'message' => 'Немате дозволу за ову акцију.',
            ], 403);
        }

        // Validate input
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:Users,UserID',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        // CRITICAL: Prevent force check-in on self
        if ($validated['user_id'] == $admin->UserID) {
            return response()->json([
                'success' => false,
                'message' => 'Не можете да пријавите самог себе преко ове функције. Користите регуларну пријаву.',
            ], 400);
        }

        $targetUser = User::find($validated['user_id']);

        // Validate user is not already checked in
        if ($targetUser->Status === 'Prijavljen') {
            return response()->json([
                'success' => false,
                'message' => 'Корисник је већ пријављен на посао.',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Create new TimeLog entry
            $timeLog = TimeLog::create([
                'UserID' => $targetUser->UserID,
                'VremePrijave' => now(),
                'VremeOdjave' => null,
                'RadniDatum' => now()->toDateString(),
                'IpAdresaPrijave' => $request->ip() ?: 'N/A',
                'IpAdresaOdjave' => null,
                'RazlogPrijave' => $validated['reason'],
                'RazlogOdjave' => null,
                'PerformedByPrijava' => $admin->UserID, // Admin who performed the action
                'PerformedByOdjava' => null,
                'Napomena' => $validated['notes'] ?? null,
            ]);

            // Update user status
            $targetUser->update(['Status' => 'Prijavljen']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Корисник {$targetUser->FirstName} {$targetUser->LastName} је пријављен.",
                'data' => [
                    'log' => $timeLog,
                    'user' => $targetUser,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Дошло је до грешке приликом пријављивања корисника.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force check-out user (Admin/Kadrovik only).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceCheckOut(Request $request)
    {
        $admin = Auth::user();

        // Validate admin role
        if (!$admin->isAdmin() && $admin->Role !== 'Kadrovik') {
            return response()->json([
                'success' => false,
                'message' => 'Немате дозволу за ову акцију.',
            ], 403);
        }

        // Validate input
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:Users,UserID',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        // CRITICAL: Prevent force check-out on self
        if ($validated['user_id'] == $admin->UserID) {
            return response()->json([
                'success' => false,
                'message' => 'Не можете да одјавите самог себе преко ове функције. Користите регуларну одјаву.',
            ], 400);
        }

        $targetUser = User::find($validated['user_id']);

        // Validate user is checked in
        if ($targetUser->Status === 'Odjavljen') {
            return response()->json([
                'success' => false,
                'message' => 'Корисник није пријављен на посао.',
            ], 400);
        }

        // Find active time log
        $timeLog = TimeLog::where('UserID', $targetUser->UserID)
            ->whereNull('VremeOdjave')
            ->latest('VremePrijave')
            ->first();

        if (!$timeLog) {
            return response()->json([
                'success' => false,
                'message' => 'Није пронађен активан лог пријаве за корисника.',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Combine notes
            $combinedNotes = $timeLog->Napomena;
            if (isset($validated['notes']) && $validated['notes']) {
                $combinedNotes = $combinedNotes
                    ? $combinedNotes . ';' . $validated['notes']
                    : $validated['notes'];
            }

            // Update time log
            $timeLog->update([
                'VremeOdjave' => now(),
                'IpAdresaOdjave' => $request->ip() ?: 'N/A',
                'RazlogOdjave' => $validated['reason'],
                'PerformedByOdjava' => $admin->UserID, // Admin who performed the action
                'Napomena' => $combinedNotes,
            ]);

            // Update user status
            $targetUser->update(['Status' => 'Odjavljen']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Корисник {$targetUser->FirstName} {$targetUser->LastName} је одјављен.",
                'data' => [
                    'log' => $timeLog,
                    'user' => $targetUser,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Дошло је до грешке приликом одјављивања корисника.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
