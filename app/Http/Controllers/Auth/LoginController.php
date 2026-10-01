<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $throttleKey = Str::transliterate(Str::lower($request->string('email')).'|'.$request->ip());

        // Máximo 5 intentos fallidos por minuto por correo + IP.
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos. Intenta de nuevo en '.RateLimiter::availableIn($throttleKey).' segundos.',
            ]);
        }

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son correctas o el usuario está desactivado.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
