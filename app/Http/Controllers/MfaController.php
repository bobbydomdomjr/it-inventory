<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TotpService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MfaController extends Controller
{
    public function __construct(private readonly TotpService $totp)
    {
    }

    public function challenge(Request $request)
    {
        abort_unless($request->user()->mfa_enabled, 404);

        return view('auth.mfa-challenge');
    }

    public function verifyChallenge(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        /** @var User $user */
        $user = $request->user();

        if (! $user->mfa_enabled || ! $user->mfa_secret || ! $this->totp->verify($user->mfa_secret, $data['code'])) {
            Audit::record('mfa_verification_failed', 'authentication', User::class, $user->id);
            throw ValidationException::withMessages(['code' => 'The verification code is invalid or has expired.']);
        }

        $request->session()->put('mfa.verified_user_id', $user->id);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        Audit::record('login', 'authentication', User::class, $user->id);

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function settings(Request $request)
    {
        $user = $request->user();
        $secret = null;
        $uri = null;

        if (! $user->mfa_enabled) {
            $secret = $request->session()->get('mfa.enrollment_secret');
            if (! $secret) {
                $secret = $this->totp->generateSecret();
                $request->session()->put('mfa.enrollment_secret', $secret);
            }
            $uri = $this->totp->provisioningUri($secret, $user->email, config('app.name'));
        }

        return view('admin.security-mfa', compact('secret', 'uri'));
    }

    public function enable(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $secret = $request->session()->get('mfa.enrollment_secret');

        if (! $secret || ! $this->totp->verify($secret, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'The verification code is invalid. Check your authenticator app and try again.']);
        }

        $request->user()->forceFill([
            'mfa_secret' => $secret,
            'mfa_enabled' => true,
        ])->save();
        $request->session()->forget('mfa.enrollment_secret');
        $request->session()->put('mfa.verified_user_id', $request->user()->id);
        Audit::record('mfa_enabled', 'authentication', User::class, $request->user()->id);

        return redirect()->route('mfa.settings')->with('success', 'Multi-factor authentication has been enabled. Keep your authenticator app available for future sign-ins.');
    }

    public function disable(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'code' => ['required', 'digits:6'],
        ]);
        $user = $request->user();

        if (! $user->mfa_enabled || ! $user->mfa_secret || ! $this->totp->verify($user->mfa_secret, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'The authenticator code is invalid.']);
        }

        $user->forceFill(['mfa_secret' => null, 'mfa_enabled' => false])->save();
        $request->session()->put('mfa.verified_user_id', $user->id);
        Audit::record('mfa_disabled', 'authentication', User::class, $user->id);

        return redirect()->route('mfa.settings')->with('success', 'Multi-factor authentication has been disabled.');
    }
}
