<?php

namespace App\Http\Middleware;

use App\Services\RegistroBitacora;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && ! $usuario->puedeAcceder()) {
            RegistroBitacora::registrar('Sesión cerrada: cuenta no vigente', $usuario->email, RegistroBitacora::DENEGADO);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Tu cuenta ya no está activa o su vigencia terminó. Contacta al administrador.']);
        }

        return $next($request);
    }
}
