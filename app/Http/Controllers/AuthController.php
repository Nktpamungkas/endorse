<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $service) {}

    public function showLoginForm(Request $request): HttpResponse|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $request->session()->regenerateToken();

        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $error = $this->service->attempt($credentials, $request);

        if ($error !== null) {
            return back()->withErrors(['username' => $error])->onlyInput('username');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.form')->with('success', 'Anda telah logout.');
    }

    public function showPasswordForm(): Response
    {
        return Inertia::render('Profile/Password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $error = $this->service->updatePassword(Auth::user(), $request->current_password, $request->password);

        if ($error !== null) {
            return back()->withErrors(['current_password' => $error]);
        }

        return back()->with('success', 'Password berhasil diubah.');
    }
}
