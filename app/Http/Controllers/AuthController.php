<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use App\Services\TwoFactorService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    protected $twoFactorService;
    protected $emailService;

    public function __construct(TwoFactorService $twoFactorService, EmailService $emailService)
    {
        $this->twoFactorService = $twoFactorService;
        $this->emailService = $emailService;
    }

    /**
     * Show login form.
     */
    public function showLoginForm()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle login attempt.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember_email' => 'boolean',
        ]);

        // Find user by email
        $user = User::where('Email', $request->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => 'Корисник са овим email-ом не постоји.',
            ]);
        }

        // Verify password
        if (!$user->verifyPassword($request->password)) {
            throw ValidationException::withMessages([
                'password' => 'Лозинка није исправна.',
            ]);
        }

        // Check if 2FA is enabled
        $twoFactorEnabled = Setting::isTwoFactorEnabled();

        if ($twoFactorEnabled) {
            // Generate and send 2FA code
            $code = $this->twoFactorService->generateCode();
            $this->twoFactorService->storeCode($code, $user->UserID);

            // Send email
            $this->emailService->send2FACode(
                $user->Email,
                $code,
                $user->FullName
            );

            return response()->json([
                'requires_2fa' => true,
                'message' => '2FA код је послат на ваш email.',
            ]);
        }

        // Login user directly (no 2FA)
        $this->loginUser($user);

        return response()->json([
            'requires_2fa' => false,
            'redirect' => $this->getRedirectPath($user),
        ]);
    }

    /**
     * Login user and create session.
     */
    protected function loginUser(User $user)
    {
        Auth::login($user, true);

        // Clear 2FA session data
        $this->twoFactorService->clearAll();

        // Regenerate session to prevent fixation
        request()->session()->regenerate();
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

    /**
     * Logout user.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * Admin logout - SuperAdmin only.
     * Logs out from web session but keeps attendance status as "Prijavljen".
     */
    public function adminLogout(Request $request)
    {
        $user = Auth::user();

        // Only SuperAdmin can use this feature
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'error' => 'Само SuperAdmin може користити Admin Logout функцију.',
            ], 403);
        }

        // Logout from web session (attendance status remains "Prijavljen")
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Admin logout успешан. Ваш статус присуства је остао "Пријављен".');
    }
}
