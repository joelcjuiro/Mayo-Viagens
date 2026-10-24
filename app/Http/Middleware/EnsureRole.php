<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso: ->middleware('role')          = el usuario debe estar activo
 *      ->middleware('role:admin')    = además debe tener ese rol
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Cuenta desactivada: se cierra la sesión
        if ($user && $user->activo === false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta está desactivada. Habla con el administrador.']);
        }

        if ($roles && ! in_array($user?->role ?? 'admin', $roles, true)) {
            abort(403, 'Tu usuario (editor) no tiene permiso para esta acción. Pídesela a un administrador.');
        }

        return $next($request);
    }
}
