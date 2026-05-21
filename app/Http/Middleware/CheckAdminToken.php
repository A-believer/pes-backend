<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminToken {
    public function handle(Request $request, Closure $next): Response {
        $token = $request->bearerToken();
        $adminKey = env('ADMIN_API_KEY');

        if (!$token || $token !== $adminKey) {
            return response()->json(['error' => 'Unauthorized access'], 401);
        }

        return $next($request);
    }
}
