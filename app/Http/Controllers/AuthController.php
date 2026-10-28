<?php

namespace App\Http\Controllers;

use App\Models\Acceso;
use App\Models\User;
use App\Services\RegistroBitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function mostrarLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = mb_strtolower(trim($datos['email']));
        $credenciales = ['email' => $email, 'password' => $datos['password']];

        if (Auth::attempt($credenciales)) {
            $usuario = Auth::user();

            // Contraseña correcta, pero la cuenta no está vigente.
            if (! $usuario->puedeAcceder()) {
                $motivo = 'cuenta_' . $usuario->estadoEfectivo();

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                Acceso::registrar($usuario->id, $email, false, $motivo);
                RegistroBitacora::registrar(
                    'Inicio de sesión rechazado: cuenta no vigente',
                    $email,
                    RegistroBitacora::DENEGADO,
                    "Estado: {$motivo}"
                );

                return back()
                    ->withErrors(['email' => 'Esta cuenta no está activa o su vigencia terminó. Contacta al administrador.'])
                    ->onlyInput('email');
            }

            $request->session()->regenerate();
            Acceso::registrar($usuario->id, $email, true);
            RegistroBitacora::registrar('Inicio de sesión');

            return redirect()->intended(route('dashboard'));
        }

        $existente = User::where('email', $email)->first();
        Acceso::registrar($existente?->id, $email, false, 'credenciales');
        RegistroBitacora::registrar('Intento de inicio de sesión fallido', $email, RegistroBitacora::ERROR);

        return back()
            ->withErrors(['email' => 'Correo o contraseña incorrectos.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        RegistroBitacora::registrar('Cierre de sesión');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
