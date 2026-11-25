<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class CheckPermission {public function handle(Request $request,Closure $next,string $permission='system.access'):Response{$user=$request->user();abort_unless($user && $user->hasPermission($permission),$user?403:401);return $next($request);}}
