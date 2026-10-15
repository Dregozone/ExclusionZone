<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateStatsToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('services.stats.token');

        if (! is_string($token) || $token === '') {
            abort(404);
        }

        $provided = (string) $request->bearerToken();

        if (! hash_equals($token, $provided)) {
            abort(401);
        }

        return $next($request);
    }
}
