<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Enums\AuditAction;
use App\Enums\BlockReason;
use App\Events\AuditLogEvent;
use App\Models\User;
use Illuminate\Auth\Events\Logout as LaravelLogout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Retrieve user by email (if exists)
        $user = User::where('email', $request->email)->first();

        // If user is blocked, prevent login
        if ($user && $user->is_blocked) {

            event(new AuditLogEvent(
                user: $user,
                action: AuditAction::BLOCKED->value,
                auditableType: User::class,
                auditableId: $user->id,
                newValues: [
                    'reason' => 'Blocked user attempted login',
                ],
                ipAddress: $request->ip(),
            ));

            throw ValidationException::withMessages([
                'email' => 'This account has been blocked due to multiple failed login attempts. Please contact an administrator.',
            ]);
        }

        try {
            $request->authenticate();
        } catch (ValidationException $e) {

            if ($user) {

                // Increment failed attempts without triggering Auditable trait
                User::withoutEvents(function () use ($user) {
                    $user->increment('failed_login_attempts');
                });
                $user->refresh();

                event(new AuditLogEvent(
                    user: $user,
                    action: AuditAction::FAILED_LOGIN->value,
                    auditableType: User::class,
                    auditableId: $user->id,
                    newValues: [
                        'attempt_number' => $user->failed_login_attempts,
                        'email' => $user->email,
                    ],
                    ipAddress: $request->ip(),
                ));

                // Auto block after 3 failed attempts
                if ($user->failed_login_attempts >= 3) {

                    User::withoutEvents(function () use ($user) {
                        $user->update([
                            'is_blocked' => true,
                            'blocked_at' => now(),
                            'block_reason' => BlockReason::FAILED_LOGIN_ATTEMPTS->value,
                        ]);
                    });

                    event(new AuditLogEvent(
                        user: $user,
                        action: AuditAction::BLOCKED->value,
                        auditableType: User::class,
                        auditableId: $user->id,
                        newValues: [
                            'reason' => 'Blocked after 3 consecutive failed login attempts',
                            'failed_attempts' => $user->failed_login_attempts,
                        ],
                        ipAddress: $request->ip(),
                    ));

                    throw ValidationException::withMessages([
                        'email' => 'This account has been blocked after 3 failed login attempts. Please contact an administrator.',
                    ]);
                }
            }

            throw $e;
        }

        // Successful login
        $request->session()->regenerate();

        /** @var User|null $authenticatedUser */
        $authenticatedUser = Auth::user();

        if ($authenticatedUser instanceof User) {

            // Reset failed login attempts without triggering Auditable trait
            // (the LOGIN audit entry is handled by LogSuccessfulLogin listener)
            User::withoutEvents(function () use ($authenticatedUser) {
                $authenticatedUser->update([
                    'failed_login_attempts' => 0,
                ]);
            });

            // Role-based redirect
            if ($authenticatedUser->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            if ($authenticatedUser->role === 'staff') {
                return redirect()->route('staff.dashboard');
            }
        }

        return redirect()->route('login');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Logout audit is handled by LogSuccessfulLogout listener
        // (fires automatically when Auth::guard('web')->logout() is called)

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
