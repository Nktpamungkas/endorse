<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserManageController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeMaster();

        $users = User::query()
            ->when($request->filled('q'), fn ($q) => $q->where('username', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->orderByDesc('role')
            ->orderBy('username')
            ->get();

        $onlineUserIds = collect();

        if (Schema::hasTable('sessions')) {
            $onlineUserIds = DB::table('sessions')
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', now()->subMinutes(15)->getTimestamp())
                ->pluck('user_id')
                ->unique();
        }

        return Inertia::render('Users/Index', [
            'users' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'role_label' => $user->role === 'paid' ? 'Berlangganan' : ucfirst($user->role),
                'trial_ends_at' => $user->trial_ends_at?->format('Y-m-d'),
                'active' => (bool) $user->active,
                'is_online' => $onlineUserIds->contains($user->id),
            ])->values(),
            'filters' => [
                'q' => (string) $request->string('q'),
                'role' => (string) $request->string('role'),
            ],
            'stats' => [
                'total_users' => $users->count(),
                'trial_count' => $users->where('role', 'trial')->count(),
                'paid_count' => $users->where('role', 'paid')->count(),
                'online_count' => $onlineUserIds->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeMaster();
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:4'],
            'trial_ends_at' => ['nullable', 'date'],
            'role' => ['nullable', 'in:trial,paid'],
        ]);

        $email = $request->input('email');
        if (empty($email)) {
            $email = $data['username'].'@trial.local';
        }

        $role = $data['role'] ?? 'trial';

        User::create([
            'name' => $data['username'],
            'email' => $email,
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'role' => $role,
            'trial_ends_at' => $role === 'trial' ? $data['trial_ends_at'] : null,
            'active' => true,
        ]);

        return back()->with('success', 'User trial berhasil dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeMaster();
        $data = $request->validate([
            'trial_ends_at' => ['nullable', 'date'],
            'active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:4'],
            'role' => ['required', 'in:trial,paid,master'],
        ]);

        $update = [
            'active' => $data['active'],
            'role' => $data['role'],
            'trial_ends_at' => $data['role'] === 'trial' ? $data['trial_ends_at'] : null,
        ];

        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);

        return back()->with('success', 'User berhasil diupdate.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeMaster();
        if ($user->role === 'master') {
            abort(403, 'Tidak boleh menghapus akun master.');
        }

        DB::transaction(function () use ($user) {
            $endorsements = $user->endorsements()->get();

            foreach ($endorsements as $endorsement) {
                if ($endorsement->checkout_proof_path) {
                    Storage::disk('public')->delete($endorsement->checkout_proof_path);
                }
            }

            $user->endorsementRevisions()->delete();
            $user->endorsements()->delete();
            $user->delete();
        });

        return back()->with('success', 'User dan seluruh datanya sudah dihapus.');
    }

    public function forceLogout(User $user): RedirectResponse
    {
        $this->authorizeMaster();
        if ($user->role === 'master') {
            abort(403, 'Tidak boleh memaksa logout akun master.');
        }

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $user->forceFill([
            'remember_token' => Str::random(60),
            'session_code' => Str::random(40),
        ])->save();

        return back()->with('success', 'User berhasil dipaksa logout.');
    }

    private function authorizeMaster(): void
    {
        if (! Auth::check() || Auth::user()->role !== 'master') {
            abort(403);
        }
    }
}
