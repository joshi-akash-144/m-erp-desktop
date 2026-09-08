<?php

namespace App\Http\Middleware;

use Closure;

class ScreenLocked
{
    public function handle($request, Closure $next)
    {
        // If the user is locked, redirect to lock screen
        if (session('locked')) {
            if (!$request->is('lock') && !$request->is('unlock') && !$request->is('lock-status')) {
                return redirect()->route('lock.screen');
            }
        }

        return $next($request);
    }
}
