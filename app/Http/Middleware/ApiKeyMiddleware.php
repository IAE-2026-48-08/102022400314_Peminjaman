<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     * Memvalidasi X-IAE-KEY header untuk keamanan API.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-IAE-KEY');
        $validKey = env('IAE_API_KEY', '102022400314');

        if (!$apiKey || $apiKey !== $validKey) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized. Invalid or missing X-IAE-KEY header.',
                'errors'  => null,
            ], 401);
        }

        return $next($request);
    }
}
