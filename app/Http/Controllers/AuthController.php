<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Services\AuditService;
use App\Services\LoginAttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        protected LoginAttemptService $attempts,
        protected AuditService $audit,
    ) {
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $username = $request->validated('username');
        $ip = $request->ip();

        if ($this->attempts->isLockedOut($username)) {
            $minutes = $this->attempts->minutesRemaining($username);

            return back()->withInput($request->only('username'))->withErrors([
                'username' => "Too many failed sign-in attempts. Try again in {$minutes} minute(s).",
            ]);
        }

        $credentials = ['username' => $username, 'password' => $request->validated('password'), 'is_active' => true];

        if (! Auth::attempt($credentials)) {
            $this->attempts->record($username, $ip, false);

            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'These credentials do not match our records, or the account is deactivated.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put('last_activity', time());

        $user = Auth::user();
        $this->attempts->record($username, $ip, true);
        $user->forceFill(['last_login_at' => now()])->save();
        $this->audit->log('auth.login', "{$user->username} signed in", $user, $user);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            $this->audit->log('auth.logout', "{$user->username} signed out", $user, $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been signed out.');
    }

    public function showChangePassword(): View
    {
        return view('auth.change-password');
    }

    public function changePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['password' => $request->validated('password'), 'must_change_password' => false]);

        $this->audit->log('auth.password_changed', "{$user->username} changed their password", $user, $user);

        return redirect()->route('dashboard')->with('success', 'Your password has been updated.');
    }
}
