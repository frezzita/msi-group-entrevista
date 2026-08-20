<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Autenticacion propia en lugar de un starter kit: Breeze dejo de recibir
 * actualizaciones a partir de Laravel 12 y arrastra Vite y npm, que este proyecto
 * no necesita. Ver STACK.md.
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->verificarIntentos($request);

        if (! Auth::attempt($credenciales, $request->boolean("recordarme"))) {
            RateLimiter::hit($this->clave($request));

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($this->clave($request));

        // Contra fijacion de sesion: el id de sesion previo al login no debe seguir
        // siendo valido despues de autenticar.
        $request->session()->regenerate();

        return redirect()->intended(route('reservas.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function verificarIntentos(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->clave($request), maxAttempts: 5)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->clave($request))]),
        ]);
    }

    private function clave(Request $request): string
    {
        return Str::transliterate(Str::lower($request->string('email')).'|'.$request->ip());
    }
}
