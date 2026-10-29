<?php

namespace App\Services;

use App\Mail\CodigoRecuperacionMail;
use App\Mail\ContrasenaCambiadaMail;
use App\Models\CodigoRecuperacion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

class Recuperacion
{
    public const MINUTOS_VIGENCIA = 15;
    public const MAX_INTENTOS = 5;       // intentos por código
    public const MAX_SOLICITUDES = 3;    // códigos por correo en la ventana
    public const VENTANA_SEGUNDOS = 900; // 15 minutos

    public const OK = 'ok';
    public const INVALIDO = 'invalido';
    public const MISMA = 'misma';

    /** Huella del código, atada a la cuenta y a la llave de la aplicación. */
    private static function huella(int $userId, string $codigo): string
    {
        return hash_hmac('sha256', $userId . '|' . $codigo, (string) config('app.key'));
    }

    /**
     * Genera y envía un código si la cuenta es elegible. No devuelve nada a propósito:
     * quien llama responde siempre lo mismo.
     */
    public static function solicitar(string $email, ?string $ip = null): void
    {
        try {
            $email = mb_strtolower(trim($email));

            $limite = 'recuperar:' . sha1($email);
            if (RateLimiter::tooManyAttempts($limite, self::MAX_SOLICITUDES)) {
                RegistroBitacora::registrar('Recuperación: demasiadas solicitudes', $email, RegistroBitacora::DENEGADO);
                return;
            }
            RateLimiter::hit($limite, self::VENTANA_SEGUNDOS);

            $usuario = User::where('email', $email)->first();

            if (! $usuario) {
                RegistroBitacora::registrar('Recuperación solicitada: sin envío', $email, RegistroBitacora::DENEGADO, 'La cuenta no existe');
                return;
            }

            if (! $usuario->puedeRecuperar()) {
                RegistroBitacora::registrar('Recuperación solicitada: sin envío', $email, RegistroBitacora::DENEGADO, 'Cuenta no elegible (' . $usuario->estadoEfectivo() . ')');
                return;
            }

            // Un código nuevo anula los anteriores.
            CodigoRecuperacion::where('user_id', $usuario->id)->whereNull('usado_at')->update(['usado_at' => now()]);

            $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            CodigoRecuperacion::create([
                'user_id'     => $usuario->id,
                'codigo_hash' => self::huella($usuario->id, $codigo),
                'expira_at'   => now()->addMinutes(self::MINUTOS_VIGENCIA),
                'ip'          => $ip,
            ]);

            // Solo en desarrollo (mailer "log"): deja el código legible en el log.
            if (config('mail.default') === 'log') {
                Log::info("CODIGO {$usuario->email}: {$codigo}");
            }

            try {
                Mail::to($usuario->email)->send(new CodigoRecuperacionMail($usuario, $codigo, self::MINUTOS_VIGENCIA));
                RegistroBitacora::registrar('Código de recuperación enviado', $usuario->email);
            } catch (Throwable $e) {
                report($e);
                RegistroBitacora::registrar('Código de recuperación: falló el envío', $usuario->email, RegistroBitacora::ERROR);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Valida el código y cambia la contraseña. Devuelve OK, INVALIDO o MISMA. */
    public static function restablecer(string $email, string $codigo, string $password, ?string $ip = null): string
    {
        $email = mb_strtolower(trim($email));
        $usuario = User::where('email', $email)->first();

        if (! $usuario || ! $usuario->puedeRecuperar()) {
            RegistroBitacora::registrar('Recuperación fallida', $email, RegistroBitacora::DENEGADO, 'Cuenta inexistente o no elegible');
            return self::INVALIDO;
        }

        $resultado = DB::transaction(function () use ($usuario, $codigo, $password) {
            $registro = CodigoRecuperacion::where('user_id', $usuario->id)
                ->whereNull('usado_at')
                ->where('expira_at', '>', now())
                ->orderByDesc('id')
                ->first();

            if (! $registro) {
                return 'sin_codigo';
            }

            // El intento se cuenta ANTES de comparar, así no se pueden probar más de 5.
            $registro->increment('intentos');
            $registro->refresh();

            if ($registro->intentos > self::MAX_INTENTOS) {
                $registro->update(['usado_at' => now()]);
                return 'agotado';
            }

            if (! hash_equals($registro->codigo_hash, self::huella($usuario->id, $codigo))) {
                if ($registro->intentos >= self::MAX_INTENTOS) {
                    $registro->update(['usado_at' => now()]);
                    return 'agotado';
                }
                return 'incorrecto';
            }

            // Código correcto: no se permite repetir la contraseña actual.
            if (Hash::check($password, $usuario->password)) {
                return self::MISMA;
            }

            // Se consume de forma atómica: solo una petición puede lograrlo.
            $filas = CodigoRecuperacion::where('id', $registro->id)->whereNull('usado_at')->update(['usado_at' => now()]);
            if ($filas !== 1) {
                return 'sin_codigo';
            }

            // Cualquier otro código pendiente de esta cuenta deja de servir.
            CodigoRecuperacion::where('user_id', $usuario->id)->whereNull('usado_at')->update(['usado_at' => now()]);

            $usuario->password = $password;            // el cast "hashed" la cifra
            $usuario->setRememberToken(Str::random(60));
            $usuario->save();

            // Cierra todas las sesiones abiertas de esa cuenta.
            DB::table('sessions')->where('user_id', $usuario->id)->delete();

            return self::OK;
        });

        if ($resultado === self::OK) {
            RegistroBitacora::registrar('Contraseña restablecida con código', $usuario->email, RegistroBitacora::EXITO, 'Sesiones abiertas cerradas');

            try {
                Mail::to($usuario->email)->send(new ContrasenaCambiadaMail(
                    $usuario,
                    now('America/Guatemala')->format('d/m/Y H:i'),
                    $ip
                ));
            } catch (Throwable $e) {
                report($e);
            }

            return self::OK;
        }

        if ($resultado === self::MISMA) {
            RegistroBitacora::registrar('Recuperación fallida', $usuario->email, RegistroBitacora::DENEGADO, 'La contraseña nueva es igual a la anterior');
            return self::MISMA;
        }

        $detalle = ['sin_codigo' => 'No hay código vigente', 'agotado' => 'Intentos agotados: el código quedó anulado', 'incorrecto' => 'Código incorrecto'][$resultado] ?? 'Código inválido';
        RegistroBitacora::registrar('Recuperación fallida', $usuario->email, RegistroBitacora::DENEGADO, $detalle);

        return self::INVALIDO;
    }
}
