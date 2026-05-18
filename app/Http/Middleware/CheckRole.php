<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // is_super_user bypasses checking if super_admin is allowed
        if ($user->is_super_user && in_array('super_admin', $roles)) {
            return $next($request);
        }

        // Check if user role matches any allowed roles
        if (!in_array(strtolower($user->role), array_map('strtolower', $roles))) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden - You do not have the required permissions.'
            ], 403);
        }

        return $next($request);
    }
}
