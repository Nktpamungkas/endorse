<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleUserAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login.form');
        }

        $user = Auth::user();

        if (! $user->active) {
            Auth::logout();
            return redirect()->route('login.form')->withErrors(['username' => 'Akun dinonaktifkan.']);
        }

        if ($user->role === 'trial' && $user->trial_ends_at && Carbon::parse($user->trial_ends_at)->isPast()) {
            Auth::logout();
            return redirect()->route('login.form')->withErrors([
                'username' => 'Masa trial Anda telah berakhir. Silakan lanjut ke paket berbayar untuk memperpanjang akses.',
            ]);
        }

        return $next($request);
    }
}
