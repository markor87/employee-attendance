<?php

namespace App\Http\Controllers;

use App\Helpers\SecurityHelper;
use App\Models\User;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\TwoFactorService;
use App\Services\EmailService;
use App\Services\LoginAttemptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    protected $twoFactorService;
    protected $emailService;
    protected $loginAttemptService;
    protected $auditService;

    public function __construct(
        TwoFactorService $twoFactorService,
        EmailService $emailService,
        LoginAttemptService $loginAttemptService,
        AuditService $auditService
    ) {
        $this->twoFactorService = $twoFactorService;
        $this->emailService = $emailService;
        $this->loginAttemptService = $loginAttemptService;
        $this->auditService = $auditService;
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
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
                'remember_email' => 'boolean',
            ]);

            // Check if account is locked out (by email or IP)
            // Use generic error message to prevent user enumeration
            if ($this->loginAttemptService->isLockedOut($request->email, $request->ip())) {
                // Still record the attempt to prevent brute force
                $this->loginAttemptService->recordFailedAttempt($request->email, $request->ip());

                // Generic error message - don't reveal account is locked
                throw ValidationException::withMessages([
                    'email' => 'Неисправан email или лозинка. Покушајте касније.',
                ]);
            }

            // Find user by email
            $user = User::where('Email', $request->email)->first();

            // Generic error message to prevent user enumeration
            if (!$user || !$user->verifyPassword($request->password)) {
                // Record failed attempt
                $this->loginAttemptService->recordFailedAttempt($request->email, $request->ip());

                // Log failed login attempt
                $this->auditService->logFailedLogin($request->email, 'invalid_credentials');

                throw ValidationException::withMessages([
                    'email' => 'Неисправан email или лозинка. Покушајте касније.',
                ]);
            }

            // Clear failed attempts on successful login
            $this->loginAttemptService->clearAttempts($request->email);

            // Log successful login
            $this->auditService->logSuccessfulLogin($user->UserID);

            // Check if 2FA is enabled (force cache refresh)
            \Cache::forget('setting.TwoFactorEnabled');
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

                // Redirect to 2FA page with success message
                return redirect()->route('2fa.show')->with('message', '2FA код је послат на ваш email.');
            }

            // Login user directly (no 2FA)
            $this->loginUser($user);

            // Redirect to appropriate dashboard
            return redirect($this->getRedirectPath($user));
        } catch (ValidationException $e) {
            // Re-throw validation exceptions
            throw $e;
        } catch (\Exception $e) {
            // Log unexpected errors with masked sensitive data
            \Log::error('Login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email_masked' => SecurityHelper::maskEmail($request->email),
                'ip_masked' => SecurityHelper::maskIp($request->ip()),
            ]);

            // Return back with error
            return back()->withErrors([
                'message' => 'Дошло је до грешке приликом пријављивања. Покушајте поново.',
            ]);
        }
    }

    /**
     * Login user and create session.
     */
    protected function loginUser(User $user)
    {
        Auth::login($user, false);

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
        $userId = Auth::id();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Log logout
        if ($userId) {
            $this->auditService->logLogout($userId);
        }

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
