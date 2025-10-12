<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TimeLog;
use App\Models\Reason;
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
            return back()->withErrors([
                'message' => 'Већ сте пријављени на посао.',
            ]);
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

            return back()->with('success', 'Успешно сте се пријавили на посао.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors([
                'message' => 'Дошло је до грешке приликом пријављивања.',
            ]);
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
            return back()->withErrors([
                'message' => 'Нисте пријављени на посао.',
            ]);
        }

        // Find active (open) time log for this user
        $timeLog = TimeLog::where('UserID', $user->UserID)
            ->whereNull('VremeOdjave')
            ->latest('VremePrijave')
            ->first();

        if (!$timeLog) {
            return back()->withErrors([
                'message' => 'Није пронађен активан лог пријаве.',
            ]);
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

            return back()->with('success', 'Успешно сте се одјавили са посла.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors([
                'message' => 'Дошло је до грешке приликом одјављивања.',
            ]);
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

        // If user is not authenticated (session expired), return 401
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

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
     * Create scheduled entry for user absence (Admin/Kadrovik only).
     * This creates a COMPLETE time log entry (check-in + check-out) for specific date/time.
     * Does NOT change user's current Status.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createScheduledEntry(Request $request)
    {
        $currentUser = Auth::user();

        // Validate input
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:Users,UserID',
            'date' => 'required|date|after_or_equal:today',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'required|date_format:H:i|after:check_in_time',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $targetUser = User::find($validated['user_id']);

        // Authorization check: Regular users can ONLY schedule for themselves
        // Admin/Kadrovik can schedule for anyone (including themselves from user dashboard)
        if ($validated['user_id'] != $currentUser->UserID) {
            if (!$currentUser->isAdmin() && $currentUser->Role !== 'Kadrovik') {
                return response()->json([
                    'success' => false,
                    'message' => 'Можете евидентирати одсуство само за себе.',
                ], 403);
            }
        }

        // CRITICAL: Prevent "Dolazak na posao" as reason
        if ($validated['reason'] === 'Dolazak na posao' || $validated['reason'] === 'Долазак на посао') {
            return response()->json([
                'success' => false,
                'message' => 'Не можете користити разлог "Долазак на посао". Овај разлог је искључиво за личну пријаву запосленог.',
            ], 400);
        }

        // Combine date + time to create full datetime
        $checkInDateTime = Carbon::createFromFormat('Y-m-d H:i', $validated['date'] . ' ' . $validated['check_in_time']);
        $checkOutDateTime = Carbon::createFromFormat('Y-m-d H:i', $validated['date'] . ' ' . $validated['check_out_time']);

        DB::beginTransaction();

        try {
            // Create COMPLETE TimeLog entry (with both check-in and check-out)
            $timeLog = TimeLog::create([
                'UserID' => $targetUser->UserID,
                'VremePrijave' => $checkInDateTime,
                'VremeOdjave' => $checkOutDateTime,
                'RadniDatum' => $validated['date'],
                'IpAdresaPrijave' => $request->ip() ?: 'N/A',
                'IpAdresaOdjave' => $request->ip() ?: 'N/A',
                'RazlogPrijave' => $validated['reason'],
                'RazlogOdjave' => $validated['reason'],
                'PerformedByPrijava' => $currentUser->UserID,
                'PerformedByOdjava' => $currentUser->UserID,
                'Napomena' => $validated['notes'] ?? null,
            ]);

            // IMPORTANT: Do NOT update user status - this is a scheduled/past entry
            // User status reflects CURRENT state, not historical records

            DB::commit();

            $message = $currentUser->UserID === $targetUser->UserID
                ? "Ваше одсуство је успешно евидентирано."
                : "Одсуство за корисника {$targetUser->FirstName} {$targetUser->LastName} је успешно евидентирано.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'log' => $timeLog,
                    'user' => $targetUser,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Дошло је до грешке приликом евидентирања одсуства.',
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

    /**
     * Get all reasons (for dropdowns in force check-in/out modals).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReasons()
    {
        try {
            $checkInReasons = Reason::where('ReasonType', 'Dolazak')->get();
            $checkOutReasons = Reason::where('ReasonType', 'Odlazak')
                ->whereNotIn('ReasonName', ['Аутоматска одјава'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'checkIn' => $checkInReasons,
                    'checkOut' => $checkOutReasons,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Грешка при учитавању разлога.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get admin reasons (for scheduled entry modal).
     * EXCLUDES "Dolazak na posao" - only for employee self check-in.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAdminReasons()
    {
        try {
            // Get all "Dolazak" reasons EXCEPT "Dolazak na posao"
            $adminReasons = Reason::where('ReasonType', 'Dolazak')
                ->where('ReasonName', '!=', 'Долазак на посао')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $adminReasons,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Грешка при учитавању разлога.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
