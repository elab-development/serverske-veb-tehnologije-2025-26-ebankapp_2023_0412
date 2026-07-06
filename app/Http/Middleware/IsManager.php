<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsManager
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || (!$request->user()->isAdmin() && !$request->user()->isManager())) {
            return response()->json([
                'success' => false,
                'message' => 'Odbijen pristup.',
            ], 403);
        }

        return $next($request);
    }
}