<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class GameRateLimiter
{
    public function handle(Request $request, Closure $next, string $limit = '60,1'): Response
    {
        [$maxAttempts, $decayMinutes] = explode(',', $limit);

        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $response->header(
            'X-RateLimit-Remaining',
            RateLimiter::remaining($key, $maxAttempts)
        );
    }

    protected function resolveRequestSignature(Request $request): string
    {
        $user = $request->user();
        $route = $request->route()?->getName() ?? $request->path();

        return sha1(
            ($user ? $user->id : $request->ip()) . '|' . $route
        );
    }
}
