<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrackPreviousUrl
{
    /**
     * Records each successful page view onto a per-company navigation
     * stack in the session, so "back" can be resolved server-side instead
     * of relying on the browser history (which doesn't know about company
     * switches).
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (
            $request->isMethod('get')
            && !$request->ajax()
            && !$request->wantsJson()
            && $request->route()?->getName() !== 'back.to.previous'
            && $response->getStatusCode() === 200
        ) {
            $companyId = session('company_id');
            $key = "nav_history.$companyId";
            $stack = session($key, []);

            $current = $request->fullUrl();
            if (end($stack) !== $current) {
                $stack[] = $current;
                $stack = array_slice($stack, -20);
                session([$key => $stack]);
            }
        }

        return $response;
    }
}
