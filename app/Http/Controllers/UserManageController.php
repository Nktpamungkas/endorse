<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserManageController extends Controller
{
    public function __construct(private readonly UserService $service) {}

    public function index(Request $request): Response
    {
        $this->authorizeMaster();

        return Inertia::render('Users/Index', $this->service->indexData([
            'q' => (string) $request->string('q'),
            'role' => (string) $request->string('role'),
        ]));
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

        $this->service->create($data, $request->input('email'));

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

        $this->service->update($user, $data);

        return back()->with('success', 'User berhasil diupdate.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeMaster();
        abort_if($user->role === 'master', 403, 'Tidak boleh menghapus akun master.');

        $this->service->delete($user);

        return back()->with('success', 'User dan seluruh datanya sudah dihapus.');
    }

    public function forceLogout(User $user): RedirectResponse
    {
        $this->authorizeMaster();
        abort_if($user->role === 'master', 403, 'Tidak boleh memaksa logout akun master.');

        $this->service->forceLogout($user);

        return back()->with('success', 'User berhasil dipaksa logout.');
    }

    private function authorizeMaster(): void
    {
        abort_if(! Auth::check() || Auth::user()->role !== 'master', 403);
    }
}
