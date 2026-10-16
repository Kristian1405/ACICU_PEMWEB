<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * AuthController (Admin)
 * ------------------------------------------------------------------
 * Menangani login/logout portal admin ACICU memakai guard session
 * bawaan Laravel (`web` + model `User`) — guard yang sama yang dipakai
 * Fortify. Karena panel admin adalah halaman Blade biasa (bukan
 * Inertia), respons diarahkan eksplisit ke /admin/dashboard agar tidak
 * bergantung pada alur tim/Inertia Fortify.
 */
class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Terima email atau nama admin ("Username / Email" pada form).
        $login = trim($credentials['username']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        $attempted = Auth::guard('web')->attempt(
            [$field => $login, 'password' => $credentials['password']],
            $request->boolean('remember'),
        );

        if (! $attempted) {
            throw ValidationException::withMessages([
                'username' => 'Email/username atau kata sandi salah.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
