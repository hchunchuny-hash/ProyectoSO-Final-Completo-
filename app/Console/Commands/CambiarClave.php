<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\RegistroBitacora;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CambiarClave extends Command
{
    protected $signature = 'usuarios:cambiar-clave {email : Correo de la cuenta}';

    protected $description = 'Cambia la contraseña de una cuenta desde la consola del servidor (recuperación de emergencia)';

    public function handle(): int
    {
        $usuario = User::where('email', mb_strtolower(trim((string) $this->argument('email'))))->first();

        if (! $usuario) {
            $this->error('No existe una cuenta con ese correo.');
            return self::FAILURE;
        }

        $clave = (string) $this->secret('Contraseña nueva (no se muestra)');
        $repite = (string) $this->secret('Repite la contraseña');

        if ($clave !== $repite) {
            $this->error('Las contraseñas no coinciden.');
            return self::FAILURE;
        }

        $validador = Validator::make(
            ['password' => $clave],
            ['password' => [Password::min(10)->letters()->mixedCase()->numbers()]]
        );

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $mensaje) {
                $this->error($mensaje);
            }
            return self::FAILURE;
        }

        $usuario->password = $clave; // el cast "hashed" la cifra
        $usuario->setRememberToken(Str::random(60));
        $usuario->save();

        DB::table('sessions')->where('user_id', $usuario->id)->delete();

        RegistroBitacora::registrar('Contraseña cambiada desde la consola', $usuario->email);

        $this->info("Contraseña de {$usuario->email} actualizada. Se cerraron sus sesiones abiertas.");

        return self::SUCCESS;
    }
}
