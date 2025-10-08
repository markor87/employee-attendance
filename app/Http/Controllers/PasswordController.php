<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PasswordController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Show change password form.
     */
    public function showChangeForm()
    {
        $user = Auth::user();

        return Inertia::render('Auth/ChangePassword', [
            'requires_change' => $user->PasswordNeedsChange,
        ]);
    }

    /**
     * Change password.
     */
    public function change(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        // Verify current password
        if (!$user->verifyPassword($request->current_password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Тренутна лозинка није исправна.',
            ]);
        }

        // Check if new password is same as current
        if ($user->verifyPassword($request->new_password)) {
            throw ValidationException::withMessages([
                'new_password' => 'Нова лозинка мора бити различита од тренутне.',
            ]);
        }

        // Validate password strength
        try {
            $this->userService->validatePasswordStrength($request->new_password);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages([
                'new_password' => 'Лозинка мора садржати минимум 8 карактера, један број и један специјални карактер.',
            ]);
        }

        // Update password
        $user->setPasswordHash($request->new_password);
        $user->PasswordNeedsChange = false;
        $user->DateUpdated = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Лозинка је успешно промењена.',
            'redirect' => '/dashboard',
        ]);
    }

    /**
     * Force password change for first-time login or admin-initiated reset.
     * Shows modal overlay that cannot be dismissed.
     */
    public function showForceChangeForm()
    {
        $user = Auth::user();

        if (!$user->PasswordNeedsChange) {
            return redirect('/dashboard');
        }

        return Inertia::render('Auth/ForceChangePassword', [
            'user' => [
                'id' => $user->UserID,
                'full_name' => $user->FullName,
                'email' => $user->Email,
            ],
        ]);
    }

    /**
     * Process forced password change.
     * Only requires new password, not current password (since it's temporary).
     */
    public function forceChange(Request $request)
    {
        $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        // Verify PasswordNeedsChange flag is set
        if (!$user->PasswordNeedsChange) {
            return response()->json([
                'error' => 'Промена лозинке није потребна.',
            ], 400);
        }

        // Validate password strength
        try {
            $this->userService->validatePasswordStrength($request->new_password);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages([
                'new_password' => 'Лозинка мора садржати минимум 8 карактера, један број и један специјални карактер.',
            ]);
        }

        // Update password
        $user->setPasswordHash($request->new_password);
        $user->PasswordNeedsChange = false;
        $user->DateUpdated = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Лозинка је успешно постављена.',
            'redirect' => '/dashboard',
        ]);
    }
}
