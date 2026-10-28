<?php

namespace App\Services;

use App\Mail\InvitacionUsuario;
use App\Models\Invitacion;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class Invitaciones
{
    public const HORAS_VIGENCIA = 48;

    /** Genera una invitación nueva, invalida las anteriores y envía el correo. */
    public static function enviar(User $usuario): bool
    {
        Invitacion::where('user_id', $usuario->id)->whereNull('usada_at')->delete();

        $token = Str::random(64);
        $expira = now()->addHours(self::HORAS_VIGENCIA);

        Invitacion::create([
            'user_id'    => $usuario->id,
            'token_hash' => hash('sha256', $token),
            'expira_at'  => $expira,
        ]);

        $enlace = url('/invitacion/' . $token);

        // Solo con el mailer "log" (desarrollo): deja el enlace legible en el log.
        if (config('mail.default') === 'log') {
            Log::info("INVITACION {$usuario->email}: {$enlace}");
        }

        try {
            Mail::to($usuario->email)->send(new InvitacionUsuario($usuario, $enlace, $expira));
            return true;
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }

    /** Devuelve la invitación si el código existe, no se ha usado y no ha vencido. */
    public static function buscarValida(string $token): ?Invitacion
    {
        return Invitacion::where('token_hash', hash('sha256', $token))
            ->whereNull('usada_at')
            ->where('expira_at', '>', now())
            ->first();
    }
}
