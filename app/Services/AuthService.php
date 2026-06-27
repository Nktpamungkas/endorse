<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;
    private const LOCK_MINUTES = 15;

    public function __construct(private readonly UserRepository $users) {}

    /** Coba login. Mengembalikan pesan error, atau null bila sukses (login dijalankan di sini). */
    public function attempt(array $credentials, Request $request): ?string
    {
        $usernameKey = mb_strtolower($credentials['username']);
        $lockKey = 'login_lock:'.$usernameKey;
        $attemptKey = 'login_attempts:'.$usernameKey;

        $lockedUntil = Cache::get($lockKey);
        if ($lockedUntil && Carbon::parse($lockedUntil)->isFuture()) {
            return 'Akun dikunci sampai '.Carbon::parse($lockedUntil)->format('d/m/Y H:i').'.';
        }

        $user = $this->users->findByUsername($credentials['username']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $attempts = (int) Cache::get($attemptKey, 0) + 1;
            Cache::put($attemptKey, $attempts, now()->addMinutes(self::WINDOW_MINUTES));
            if ($attempts >= self::MAX_ATTEMPTS) {
                $lockUntil = now()->addMinutes(self::LOCK_MINUTES);
                Cache::put($lockKey, $lockUntil, $lockUntil);
            }

            $this->logActivity($user?->id, $credentials['username'], false, $request);

            return 'Username atau password salah.';
        }

        if (! $user->active) {
            return 'Akun dinonaktifkan.';
        }

        if ($user->role === 'trial' && $user->trial_ends_at && Carbon::parse($user->trial_ends_at)->isPast()) {
            return 'Masa trial Anda telah berakhir. Silakan lanjut ke paket berbayar untuk memperpanjang akses.';
        }

        $code = Str::random(40);
        $this->users->setSessionCode($user, $code);

        Cache::forget($attemptKey);
        Cache::forget($lockKey);

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('user_session_code', $code);

        $this->logActivity($user->id, $user->username, true, $request);

        return null;
    }

    /** Ubah password. Mengembalikan pesan error, atau null bila sukses. */
    public function updatePassword(User $user, string $current, string $new): ?string
    {
        if (! Hash::check($current, $user->password)) {
            return 'Password lama tidak sesuai.';
        }

        $this->users->update($user, ['password' => Hash::make($new)]);

        return null;
    }

    private function logActivity(?int $userId, string $username, bool $success, Request $request): void
    {
        $this->users->logLoginActivity(
            $userId,
            $username,
            $success,
            (string) $request->ip(),
            (string) $request->userAgent(),
        );
    }
}
