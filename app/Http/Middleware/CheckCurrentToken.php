<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckCurrentToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->current_token !== $request->bearerToken()) {
            return response()->json(['message' => 'Token is invalid or expired.'], 401);
        }

        return $next($request);
    }

    protected function isValidToken(Request $request)
    {
        // Add your token validation logic here
        $token = $request->bearerToken();
        // Assume you have a method to validate token
        return $this->validateToken($token);
    }

    protected function validateToken($token)
    {
        // Your token validation logic here
        return true; // For demonstration purposes, assume it's valid
    }
}
