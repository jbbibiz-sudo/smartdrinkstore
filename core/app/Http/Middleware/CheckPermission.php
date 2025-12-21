<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = $request->user();
        if (!$user || !$user->hasPermission($permission)) {
            return response()->json(['success'=>false,'message'=>'Permission refusée'],403);
        }
        return $next($request);
    }
}
