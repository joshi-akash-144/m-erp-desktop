<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadOnlySession
{
    public function handle(Request $request, Closure $next): Response
    {
        // We ONLY want to block session writes on GET requests (data fetching).
        // POST/PUT/DELETE requests often NEED to write to the session
        // (e.g., Company Switch, Login, or saving flash messages).
        if (!$request->isMethod('GET')) {
            return $next($request); // Allow normal session behavior
        }

        $response = $next($request);

        // Apply to ALL GET requests (AJAX, Fetches, and normal HTML Views)
        if ($request->hasSession()) {
            $session = $request->session();

            // Replace the session handler with a no-op so that
            // StartSession::terminate() won't fire the UPDATE query
            $property = new \ReflectionProperty($session, 'handler');
            $property->setValue($session, new class implements \SessionHandlerInterface {
                public function open(string $path, string $name): bool
                {
                    return true;
                }
                public function close(): bool
                {
                    return true;
                }
                public function read(string $id): string|false
                {
                    return '';
                }
                public function write(string $id, string $data): bool
                {
                    return true;
                }
                public function destroy(string $id): bool
                {
                    return true;
                }
                public function gc(int $max_lifetime): int|false
                {
                    return 0;
                }
            });
        }

        return $response;
    }
}
