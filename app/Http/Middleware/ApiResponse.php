<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add common headers for API responses
        if ($request->is('api/*')) {
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('X-API-Version', '1.0.0');
            $response->headers->set('X-Powered-By', 'Charlie Unicorn API');
        }

        return $response;
    }
}
