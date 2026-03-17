<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetSessionLifetime
{
    public function handle(Request $request, Closure $next): Response
    {
        $minutes = config('session.lifetime');
        $user = Auth::user();

        if ($user) {
            $minutes = match ($user->role) {
                'master' => 480,      // 8 jam
                'paid' => 480,        // 8 jam untuk pelanggan berbayar
                'trial' => 120,       // 2 jam
                default => 240,       // 4 jam
            };
        }

        config(['session.lifetime' => $minutes]);

        return $next($request);
    }
}
