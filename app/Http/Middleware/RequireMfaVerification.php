<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMfaVerification
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->mfa_enabled && (int) $request->session()->get('mfa.verified_user_id') !== (int) $user->id) {
            return redirect()->route('mfa.challenge');
        }

        return $next($request);
    }
}
