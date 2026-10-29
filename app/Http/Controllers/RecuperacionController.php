<?php

namespace App\Http\Controllers;

use App\Services\Recuperacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class RecuperacionController extends Controller
{
    public function solicitar()
    {
        return view('recuperar.solicitar');
    }

    public function enviar(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email', 'max:150'],
        ]);

        $email = mb_strtolower(trim($datos['email']));
        $ip = $request->ip();

        // Se procesa DESPUÉS de responder, para que el tiempo de respuesta
        // sea el mismo exista o no la cuenta.
        app()->terminating(fn () => Recuperacion::solicitar($email, $ip));

        return redirect()->route('recuperar.codigo')
            ->with('correo_recuperacion', $email)
            ->with('status', 'Si el correo está registrado y la cuenta está activa, enviamos un código de 6 dígitos. Vence en ' . Recuperacion::MINUTOS_VIGENCIA . ' minutos.');
    }

    public function formularioCodigo()
    {
        return view('recuperar.codigo', [
            'correo' => old('email', session('correo_recuperacion', '')),
        ]);
    }

    public function restablecer(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $email = $request->input('email');

        $datos = $request->validate([
            'email'    => ['required', 'email', 'max:150'],
            'codigo'   => ['required', 'regex:/^[0-9]{6}$/'],
            'password' => [
                'required',
                'confirmed',
                Password::min(10)->letters()->mixedCase()->numbers(),
                function (string $atributo, mixed $valor, \Closure $falla) use ($email) {
                    if (mb_strtolower((string) $valor) === $email) {
                        $falla('La contraseña no puede ser igual a tu correo.');
                    }
                },
            ],
        ], [
            'codigo.required'    => 'Escribe el código de 6 dígitos.',
            'codigo.regex'       => 'El código son 6 números.',
            'password.required'  => 'Escribe una contraseña nueva.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
            'password.min'       => 'La contraseña debe tener al menos 10 caracteres.',
            'password.letters'   => 'La contraseña debe incluir letras.',
            'password.mixed'     => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.numbers'   => 'La contraseña debe incluir al menos un número.',
        ]);

        $resultado = Recuperacion::restablecer($datos['email'], $datos['codigo'], $datos['password'], $request->ip());

        if ($resultado === Recuperacion::OK) {
            return redirect()->route('login')
                ->with('status', 'Tu contraseña fue cambiada y se cerraron tus sesiones abiertas. Ya puedes iniciar sesión.');
        }

        if ($resultado === Recuperacion::MISMA) {
            return back()->withErrors(['password' => 'Elige una contraseña distinta a la anterior.'])->onlyInput('email');
        }

        return back()
            ->withErrors(['codigo' => 'Código incorrecto o vencido. Si ya no sirve, solicita uno nuevo.'])
            ->onlyInput('email');
    }
}
