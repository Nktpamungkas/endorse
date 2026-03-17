<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    private int $loginMaxAttempts = 5;
    private int $loginWindowMinutes = 15;
    private int $lockMinutes = 15;

    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $usernameKey = mb_strtolower($credentials['username']);
        $lockKey = 'login_lock:'.$usernameKey;
        $attemptKey = 'login_attempts:'.$usernameKey;

        $lockedUntil = Cache::get($lockKey);
        if ($lockedUntil && Carbon::parse($lockedUntil)->isFuture()) {
            return back()->withErrors([
                'username' => 'Akun dikunci sampai '.Carbon::parse($lockedUntil)->format('d/m/Y H:i').'.',
            ])->onlyInput('username');
        }

        $user = User::where('username', $credentials['username'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $attempts = (int) Cache::get($attemptKey, 0) + 1;
            Cache::put($attemptKey, $attempts, now()->addMinutes($this->loginWindowMinutes));
            if ($attempts >= $this->loginMaxAttempts) {
                $lockUntil = now()->addMinutes($this->lockMinutes);
                Cache::put($lockKey, $lockUntil, $lockUntil);
            }

            $this->logLoginActivity($user?->id, $credentials['username'], false, $request);

            return back()->withErrors([
                'username' => 'Username atau password salah.',
            ])->onlyInput('username');
        }

        if (! $user->active) {
            return back()->withErrors(['username' => 'Akun dinonaktifkan.'])->onlyInput('username');
        }

        if ($user->role === 'trial' && $user->trial_ends_at && Carbon::parse($user->trial_ends_at)->isPast()) {
            return back()->withErrors(['username' => 'Masa trial Anda telah berakhir. Silakan lanjut ke paket berbayar untuk memperpanjang akses.'])->onlyInput('username');
        }

        $newSessionCode = Str::random(40);
        $user->forceFill(['session_code' => $newSessionCode])->save();

        Cache::forget($attemptKey);
        Cache::forget($lockKey);

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('user_session_code', $newSessionCode);

        $this->logLoginActivity($user->id, $user->username, true, $request);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.form')->with('success', 'Anda telah logout.');
    }

    public function showPasswordForm(): View
    {
        return view('auth.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = Auth::user();
        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password lama tidak sesuai.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password berhasil diubah.');
    }

    private function logLoginActivity(?int $userId, string $username, bool $success, Request $request): void
    {
        if (! Schema::hasTable('user_login_activities')) {
            return;
        }

        DB::table('user_login_activities')->insert([
            'user_id' => $userId,
            'username' => $username,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'success' => $success,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
