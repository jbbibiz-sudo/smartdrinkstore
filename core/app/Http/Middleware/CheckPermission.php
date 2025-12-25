<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        // If no permissions specified, just pass through
        if (empty($permissions)) {
            return $next($request);
        }

        $user = $request->user();
        
        // Check if user has any of the required permissions
        foreach ($permissions as $permission) {
            if (!$user || !$user->hasPermission($permission)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission refusée'
                ], 403);
            }
        }
        
        return $next($request);
    }
}