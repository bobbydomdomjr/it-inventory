<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        if ($request->user()?->must_change_password && ! in_array($routeName, ['password.change', 'password.update', 'logout'], true)) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
