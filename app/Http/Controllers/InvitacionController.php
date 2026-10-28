<?php

namespace App\Http\Controllers;

use App\Models\Invitacion;
use App\Services\Invitaciones;
use App\Services\RegistroBitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class InvitacionController extends Controller
{
    public function mostrar(string $token)
    {
        $invitacion = $this->validar($token);
        if (! $invitacion) {
            return $this->invalida();
        }

        return view('invitacion.definir', [
            'usuario' => $invitacion->usuario,
            'token'   => $token,
        ]);
    }

    public function aceptar(Request $request, string $token)
    {
        $invitacion = $this->validar($token);
        if (! $invitacion) {
            return $this->invalida();
        }

        $usuario = $invitacion->usuario;

        $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(10)->letters()->mixedCase()->numbers(),
                function (string $atributo, mixed $valor, \Closure $falla) use ($usuario) {
                    if (mb_strtolower((string) $valor) === mb_strtolower($usuario->email)) {
                        $falla('La contraseña no puede ser igual a tu correo.');
                    }
                },
            ],
        ], [
            'password.required'  => 'Escribe una contraseña.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
            'password.min'       => 'La contraseña debe tener al menos 10 caracteres.',
            'password.letters'   => 'La contraseña debe incluir letras.',
            'password.mixed'     => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.numbers'   => 'La contraseña debe incluir al menos un número.',
        ]);

        // Se consume la invitación de forma atómica: si dos personas (o dos
        // pestañas) usan el enlace a la vez, solo una lo logra.
        $consumida = DB::transaction(function () use ($invitacion, $usuario, $request) {
            $filas = Invitacion::where('id', $invitacion->id)
                ->whereNull('usada_at')
                ->update(['usada_at' => now()]);

            if ($filas !== 1) {
                return false;
            }

            $usuario->password = $request->input('password'); // el cast "hashed" la cifra
            $usuario->estado = 'activo';
            $usuario->email_verified_at = now();               // el correo quedó comprobado
            $usuario->save();

            return true;
        });

        if (! $consumida) {
            return $this->invalida();
        }

        RegistroBitacora::registrar('Invitación aceptada: cuenta activada', $usuario->email);

        return redirect()->route('login')
            ->with('status', 'Tu contraseña quedó definida. Ya puedes iniciar sesión.');
    }

    private function validar(string $token): ?Invitacion
    {
        $invitacion = Invitaciones::buscarValida($token);
        $usuario = $invitacion?->usuario;

        if (! $invitacion || ! $usuario || $usuario->estado !== 'pendiente' || $usuario->estaVencido()) {
            return null;
        }

        return $invitacion;
    }

    private function invalida()
    {
        RegistroBitacora::registrar('Invitación inválida o vencida', null, RegistroBitacora::DENEGADO);

        return response()->view('invitacion.invalida', [], 410);
    }
}
