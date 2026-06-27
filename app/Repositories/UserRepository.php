<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserRepository
{
    public function list(array $filters): Collection
    {
        return User::query()
            ->when(! empty($filters['q']), fn ($q) => $q->where('username', 'like', '%'.$filters['q'].'%'))
            ->when(! empty($filters['role']), fn ($q) => $q->where('role', $filters['role']))
            ->orderByDesc('role')
            ->orderBy('username')
            ->get();
    }

    /** ID user yang aktif dalam 15 menit terakhir (dari tabel sessions). */
    public function onlineUserIds(): SupportCollection
    {
        if (! Schema::hasTable('sessions')) {
            return new SupportCollection();
        }

        return DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(15)->getTimestamp())
            ->pluck('user_id')
            ->unique()
            ->values();
    }

    public function findByUsername(string $username): ?User
    {
        return User::where('username', $username)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    public function endorsementProofPaths(User $user): array
    {
        return $user->endorsements()
            ->whereNotNull('checkout_proof_path')
            ->pluck('checkout_proof_path')
            ->all();
    }

    /** Hapus user beserta seluruh data turunannya (revisi + endorsement). */
    public function purge(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->endorsementRevisions()->delete();
            $user->endorsements()->delete();
            $user->delete();
        });
    }

    public function clearSessions(int $userId): void
    {
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $userId)->delete();
        }
    }

    public function rotateAuthTokens(User $user): void
    {
        $user->forceFill([
            'remember_token' => Str::random(60),
            'session_code' => Str::random(40),
        ])->save();
    }

    public function setSessionCode(User $user, string $code): void
    {
        $user->forceFill(['session_code' => $code])->save();
    }

    public function logLoginActivity(?int $userId, string $username, bool $success, string $ip, string $userAgent): void
    {
        if (! Schema::hasTable('user_login_activities')) {
            return;
        }

        DB::table('user_login_activities')->insert([
            'user_id' => $userId,
            'username' => $username,
            'ip_address' => $ip,
            'user_agent' => substr($userAgent, 0, 255),
            'success' => $success,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
