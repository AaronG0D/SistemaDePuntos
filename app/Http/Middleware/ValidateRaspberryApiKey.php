<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateRaspberryApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $provided = $request->header('X-API-KEY');
        $expected = env('RASPBERRY_API_KEY');

        if (!$provided || !$expected || !hash_equals((string)$expected, (string)$provided)) {
            return response()->json([
                'message' => 'Unauthorized - Invalid API Key'
            ], 401);
        }

        return $next($request);
    }
}
