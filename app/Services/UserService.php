<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function __construct(private readonly UserRepository $repo) {}

    public function indexData(array $filters): array
    {
        $users = $this->repo->list($filters);
        $online = $this->repo->onlineUserIds();

        return [
            'users' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'role_label' => $user->role === 'paid' ? 'Berlangganan' : ucfirst($user->role),
                'trial_ends_at' => $user->trial_ends_at?->format('Y-m-d'),
                'active' => (bool) $user->active,
                'is_online' => $online->contains($user->id),
            ])->values(),
            'filters' => [
                'q' => $filters['q'] ?? '',
                'role' => $filters['role'] ?? '',
            ],
            'stats' => [
                'total_users' => $users->count(),
                'trial_count' => $users->where('role', 'trial')->count(),
                'paid_count' => $users->where('role', 'paid')->count(),
                'online_count' => $online->count(),
            ],
        ];
    }

    public function create(array $data, ?string $email): User
    {
        $role = $data['role'] ?? 'trial';

        return $this->repo->create([
            'name' => $data['username'],
            'email' => $email ?: $data['username'].'@trial.local',
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'role' => $role,
            'trial_ends_at' => $role === 'trial' ? ($data['trial_ends_at'] ?? null) : null,
            'active' => true,
        ]);
    }

    public function update(User $user, array $data): User
    {
        $update = [
            'active' => $data['active'],
            'role' => $data['role'],
            'trial_ends_at' => $data['role'] === 'trial' ? ($data['trial_ends_at'] ?? null) : null,
        ];

        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        return $this->repo->update($user, $update);
    }

    public function delete(User $user): void
    {
        foreach ($this->repo->endorsementProofPaths($user) as $path) {
            Storage::disk('public')->delete($path);
        }

        $this->repo->purge($user);
    }

    public function forceLogout(User $user): void
    {
        $this->repo->clearSessions($user->id);
        $this->repo->rotateAuthTokens($user);
    }
}
