<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnforceModulePermissions;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\RequireMfaVerification;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => CheckPermission::class,
            'module.permission' => EnforceModulePermissions::class,
            'password.change' => RequirePasswordChange::class,
            'mfa.verified' => RequireMfaVerification::class,
        ]);
        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Laravel's default exception handler is registered here.
    })
    ->create();
