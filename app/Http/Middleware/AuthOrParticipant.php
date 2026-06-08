<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthOrParticipant
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() || $request->session()->has('participant')) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
