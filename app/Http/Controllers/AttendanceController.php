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

        // Authorization check:
        // - Admin/Kadrovik can schedule for anyone
        // - Rukovodilac can schedule ONLY for users in their sector
        // - Regular users can ONLY schedule for themselves
        if ($validated['user_id'] != $currentUser->UserID) {
            // Check if user is Admin or Kadrovik (full access)
            if (!$currentUser->isAdmin() && $currentUser->Role !== 'Kadrovik') {
                // Check if user is Rukovodilac (sector-limited access)
                if ($currentUser->Role === 'Rukovodilac') {
                    // Rukovodilac must have a sector assigned
                    if (!$currentUser->sector_id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Немате додељен сектор.',
                        ], 403);
                    }

                    // Target user must be in the same sector
                    if ($targetUser->sector_id !== $currentUser->sector_id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Можете евидентирати одсуство само за кориснике из вашег сектора.',
                        ], 403);
                    }
                } else {
                    // Regular users can only schedule for themselves
                    return response()->json([
                        'success' => false,
                        'message' => 'Можете евидентирати одсуство само за себе.',
                    ], 403);
                }
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

    /**
     * Delete a time log entry.
     * ONLY the user who created the log (PerformedByPrijava) can delete it.
     * ONLY non-expired logs can be deleted (VremeOdjave must be in the future or null).
     *
     * @param int $logId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteLog($logId)
    {
        $currentUser = Auth::user();

        // Find the log
        $log = TimeLog::find($logId);

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Лог није пронађен.',
            ], 404);
        }

        // Authorization: ONLY the creator can delete
        if ($log->PerformedByPrijava !== $currentUser->UserID) {
            return response()->json([
                'success' => false,
                'message' => 'Немате дозволу да обришете овај лог. Можете брисати само логове које сте сами креирали.',
            ], 403);
        }

        // Check if log is expired (VremeOdjave is in the past)
        if ($log->VremeOdjave && $log->VremeOdjave->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Не можете брисати истекли лог.',
            ], 403);
        }

        // IMPORTANT: Cannot delete active check-ins (where VremeOdjave is null and user is currently checked in)
        // But scheduled future entries with both times can be deleted
        if ($log->VremeOdjave === null) {
            // This is an active check-in, not a scheduled entry
            return response()->json([
                'success' => false,
                'message' => 'Не можете брисати активну пријаву. Одјавите се прво.',
            ], 403);
        }

        try {
            $log->delete();

            return response()->json([
                'success' => true,
                'message' => 'Лог је успешно обрисан.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Дошло је до грешке приликом брисања лога.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a time log entry.
     * ONLY the user who created the log (PerformedByPrijava) can update it.
     * ONLY non-expired logs can be updated (VremeOdjave must be in the future).
     *
     * @param Request $request
     * @param int $logId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLog(Request $request, $logId)
    {
        $currentUser = Auth::user();

        // Find the log
        $log = TimeLog::find($logId);

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Лог није пронађен.',
            ], 404);
        }

        // Authorization: ONLY the creator can update
        if ($log->PerformedByPrijava !== $currentUser->UserID) {
            return response()->json([
                'success' => false,
                'message' => 'Немате дозволу да измените овај лог. Можете мењати само логове које сте сами креирали.',
            ], 403);
        }

        // Check if log is expired (VremeOdjave is in the past)
        if ($log->VremeOdjave && $log->VremeOdjave->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Не можете мењати истекли лог.',
            ], 403);
        }

        // Cannot update active check-ins
        if ($log->VremeOdjave === null) {
            return response()->json([
                'success' => false,
                'message' => 'Не можете мењати активну пријаву.',
            ], 403);
        }

        // Validate input
        $validated = $request->validate([
            'date' => 'required|date',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'required|date_format:H:i|after:check_in_time',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        // Combine date + time to create full datetime
        $checkInDateTime = Carbon::createFromFormat('Y-m-d H:i', $validated['date'] . ' ' . $validated['check_in_time']);
        $checkOutDateTime = Carbon::createFromFormat('Y-m-d H:i', $validated['date'] . ' ' . $validated['check_out_time']);

        // Ensure the updated times are not in the past (still valid for editing)
        if ($checkOutDateTime->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Ново време одјаве не сме бити у прошлости.',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Update log
            $log->update([
                'VremePrijave' => $checkInDateTime,
                'VremeOdjave' => $checkOutDateTime,
                'RadniDatum' => $validated['date'],
                'RazlogPrijave' => $validated['reason'],
                'RazlogOdjave' => $validated['reason'],
                'Napomena' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Лог је успешно ажуриран.',
                'data' => $log,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Дошло је до грешке приликом ажурирања лога.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if user needs overtime presence confirmation prompt.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkOvertimeStatus(Request $request)
    {
        $user = Auth::user();

        // If not checked in, return success
        if ($user->Status !== 'Prijavljen') {
            return response()->json([
                'needs_prompt' => false,
                'is_checked_in' => false,
            ]);
        }

        // Find active session
        $activeLog = TimeLog::where('UserID', $user->UserID)
            ->whereNull('VremeOdjave')
            ->latest('VremePrijave')
            ->first();

        if (! $activeLog) {
            return response()->json([
                'needs_prompt' => false,
                'is_checked_in' => false,
            ]);
        }

        // Get overtime check time from settings (default 15:30)
        $overtimeCheckTime = DB::table('Settings')
            ->where('SettingKey', 'overtime_check_time')
            ->value('SettingValue') ?? '15:30';

        $currentTime = now();
        $checkTime = Carbon::parse($currentTime->format('Y-m-d').' '.$overtimeCheckTime);

        // If it's not yet time for check
        if ($currentTime->lt($checkTime)) {
            return response()->json([
                'needs_prompt' => false,
                'is_checked_in' => true,
                'next_check_at' => $checkTime->toIso8601String(),
            ]);
        }

        // Check if today is a working day
        $workingDays = explode(',', DB::table('Settings')
            ->where('SettingKey', 'overtime_working_days')
            ->value('SettingValue') ?? 'Mon,Tue,Wed,Thu,Fri');

        if (! in_array($currentTime->format('D'), $workingDays)) {
            return response()->json([
                'needs_prompt' => false,
                'is_checked_in' => true,
                'reason' => 'not_working_day',
            ]);
        }

        // Get prompt interval (default 15 minutes)
        $promptInterval = (int) (DB::table('Settings')
            ->where('SettingKey', 'overtime_prompt_interval')
            ->value('SettingValue') ?? 15);

        $lastPromptAt = $user->overtime_prompt_shown_at ? Carbon::parse($user->overtime_prompt_shown_at) : null;

        // If prompt was shown and interval hasn't passed yet
        if ($lastPromptAt && $currentTime->diffInMinutes($lastPromptAt) < $promptInterval) {
            return response()->json([
                'needs_prompt' => false,
                'is_checked_in' => true,
                'next_prompt_at' => $lastPromptAt->addMinutes($promptInterval)->toIso8601String(),
            ]);
        }

        // NEEDS PROMPT
        $promptTimeout = (int) (DB::table('Settings')
            ->where('SettingKey', 'overtime_prompt_timeout')
            ->value('SettingValue') ?? 10);

        return response()->json([
            'needs_prompt' => true,
            'is_checked_in' => true,
            'prompt_timeout' => $promptTimeout,
            'message' => 'Прошло је радно време. Да ли сте и даље на послу?',
        ]);
    }

    /**
     * User confirms overtime presence.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmOvertimePresence(Request $request)
    {
        $user = Auth::user();

        // Update last activity and prompt shown timestamp
        $user->last_activity_at = now();
        $user->overtime_prompt_shown_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Хвала на потврди. Наставите са радом.',
        ]);
    }

    /**
     * Auto checkout user if they don't respond to overtime prompt.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function autoCheckoutOvertime(Request $request)
    {
        $user = Auth::user();

        // Check if user is checked in
        if ($user->Status !== 'Prijavljen') {
            return response()->json([
                'success' => false,
                'message' => 'Корисник није пријављен.',
            ], 400);
        }

        // Find active session
        $timeLog = TimeLog::where('UserID', $user->UserID)
            ->whereNull('VremeOdjave')
            ->latest('VremePrijave')
            ->first();

        if (! $timeLog) {
            return response()->json([
                'success' => false,
                'message' => 'Није пронађена активна сесија.',
            ], 404);
        }

        DB::beginTransaction();

        try {
            // Auto checkout user
            $timeLog->update([
                'VremeOdjave' => now(),
                'RazlogOdjave' => 'Аутоматска одјава (одсуство одговора на присуство)',
                'IpAdresaOdjave' => $request->ip() ?: 'N/A',
                'PerformedByOdjava' => $user->UserID,
                'overtime_auto_checkout' => true,
                'overtime_notes' => 'Корисник није одговорио на упит о присуству након истека времена.',
            ]);

            // Update user status
            $user->update(['Status' => 'Odjavljen']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Аутоматски одјављени због непотврде присуства.',
                'redirect' => route('login'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Грешка приликом аутоматске одјаве.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
