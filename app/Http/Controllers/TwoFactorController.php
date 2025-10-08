<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactorService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TwoFactorController extends Controller
{
    protected $twoFactorService;
    protected $emailService;

    public function __construct(TwoFactorService $twoFactorService, EmailService $emailService)
    {
        $this->twoFactorService = $twoFactorService;
        $this->emailService = $emailService;
    }

    /**
     * Show 2FA verification page.
     */
    public function show()
    {
        // Check if there's a pending 2FA code
        if (!$this->twoFactorService->hasValidCode()) {
            return redirect('/login')->with('error', '2FA сесија је истекла. Молимо пријавите се поново.');
        }

        $remaining = $this->twoFactorService->getRemainingSeconds();

        return Inertia::render('Auth/TwoFactor', [
            'remaining_seconds' => $remaining,
        ]);
    }

    /**
     * Verify 2FA code.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        // Verify code
        if (!$this->twoFactorService->verifyCode($request->code)) {
            throw ValidationException::withMessages([
                'code' => 'Неисправан или истекао 2FA код.',
            ]);
        }

        // Get user ID from session
        $userId = $this->twoFactorService->getUserId();

        if (!$userId) {
            return response()->json([
                'error' => '2FA сесија није пронађена.',
            ], 400);
        }

        // Find user
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'error' => 'Корисник није пронађен.',
            ], 404);
        }

        // Login user
        Auth::login($user, true);

        // Clear 2FA session data
        $this->twoFactorService->clearAll();

        // Regenerate session
        $request->session()->regenerate();

        // Get redirect path
        $redirect = $this->getRedirectPath($user);

        return response()->json([
            'success' => true,
            'redirect' => $redirect,
        ]);
    }

    /**
     * Resend 2FA code.
     */
    public function resend(Request $request)
    {
        // Get user ID from session
        $userId = $this->twoFactorService->getUserId();

        if (!$userId) {
            return response()->json([
                'error' => '2FA сесија није пронађена.',
            ], 400);
        }

        // Find user
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'error' => 'Корисник није пронађен.',
            ], 404);
        }

        // Generate new code
        $code = $this->twoFactorService->generateCode();
        $this->twoFactorService->storeCode($code, $user->UserID);

        // Send email
        $sent = $this->emailService->send2FACode(
            $user->Email,
            $code,
            $user->FullName
        );

        if (!$sent) {
            return response()->json([
                'error' => 'Грешка при слању email-а.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Нови 2FA код је послат на ваш email.',
            'remaining_seconds' => $this->twoFactorService->getRemainingSeconds(),
        ]);
    }

    /**
     * Get redirect path based on user role and password status.
     */
    protected function getRedirectPath(User $user): string
    {
        // If password needs change, redirect to change password page
        if ($user->PasswordNeedsChange) {
            return '/change-password';
        }

        // Redirect to dashboard
        return '/dashboard';
    }
}
