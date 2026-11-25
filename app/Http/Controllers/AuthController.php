<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required|string']);

        $email = Str::lower(trim((string) $request->input('email')));
        $key = $email.'|'.$request->ip();
        $user = User::query()->where('email', $email)->first();

        if ($user?->locked_until && $user->locked_until->isFuture()) {
            $this->recordLogin($user, $email, 'locked', $request);
            throw ValidationException::withMessages([
                'email' => 'This account is temporarily locked. Try again after '.$user->locked_until->format('h:i A').'.',
            ]);
        }

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->recordLogin($user, $email, 'rate_limited', $request);
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        if (! Auth::attempt(['email' => $email, 'password' => $request->input('password')], $request->boolean('remember'))) {
            RateLimiter::hit($key, 300);

            if ($user) {
                $attempts = ((int) $user->failed_login_attempts) + 1;
                $user->forceFill([
                    'failed_login_attempts' => $attempts,
                    'locked_until' => $attempts >= 5 ? now()->addMinutes(5) : null,
                ])->save();
            }

            $this->recordLogin($user, $email, 'failed', $request);
            Audit::record('login_failed', 'authentication', User::class, $user?->id, [], ['email' => $email]);
            throw ValidationException::withMessages(['email' => 'The email address or password is incorrect.']);
        }

        /** @var User $authenticated */
        $authenticated = Auth::user();
        if (! $authenticated->is_active) {
            Auth::logout();
            $this->recordLogin($authenticated, $email, 'inactive', $request);
            throw ValidationException::withMessages(['email' => 'This account is inactive. Please contact your system administrator.']);
        }

        RateLimiter::clear($key);
        $authenticated->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();
        $request->session()->regenerate();

        if ($authenticated->mfa_enabled) {
            $request->session()->forget('mfa.verified_user_id');
            $this->recordLogin($authenticated, $email, 'mfa_pending', $request);
            Audit::record('mfa_pending', 'authentication', User::class, $authenticated->id);
            return redirect()->route('mfa.challenge');
        }

        $request->session()->put('mfa.verified_user_id', $authenticated->id);
        $authenticated->forceFill(['last_login_at' => now()])->save();
        $this->recordLogin($authenticated, $email, 'success', $request);
        Audit::record('login', 'authentication', User::class, $authenticated->id);

        if ($authenticated->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function editPassword()
    {
        return view('auth.change-password');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:12', 'confirmed', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/'],
        ], [
            'password.regex' => 'Use a password with uppercase and lowercase letters and at least one number.',
        ]);

        $request->user()->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        Audit::record('password_changed', 'authentication', User::class, $request->user()->id);
        return redirect()->route('dashboard')->with('success', 'Your password has been updated.');
    }

    public function destroy(Request $request)
    {
        Audit::record('logout', 'authentication', User::class, $request->user()?->id);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function recordLogin(?User $user, string $email, string $status, Request $request): void
    {
        LoginHistory::create([
            'user_id' => $user?->id,
            'email' => $email,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'status' => $status,
            'logged_at' => now(),
        ]);
    }
}
