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
        $sessionCode = $request->session()->get('user_session_code');

        if ($user->session_code && $sessionCode && $sessionCode !== $user->session_code) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login.form')->withErrors(['username' => 'Sesi Anda telah di-logout. Silakan login ulang.']);
        }

        if (! $sessionCode && $user->session_code) {
            // Set session code if missing (mis. setelah remember me login)
            $request->session()->put('user_session_code', $user->session_code);
        }

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
