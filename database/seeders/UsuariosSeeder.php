<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        $this->crear('Administrador', 'admin@sysmonitor.local', env('ADMIN_PASSWORD'), 'admin', true);
        $this->crear('Observador', 'observador@sysmonitor.local', env('OBSERVER_PASSWORD'), 'observador');
    }

    private function crear(string $nombre, string $correo, ?string $clave, string $rol, bool $principal = false): void
    {
        if (!$clave) {
            $this->command->error("Falta la contraseña de $correo en el archivo .env");
            return;
        }

        $usuario = User::firstOrNew(['email' => $correo]);
        $usuario->name = $nombre;
        $usuario->password = Hash::make($clave);
        $usuario->role = $rol;
        $usuario->estado = 'activo';
        $usuario->es_principal = $principal;
        $usuario->save();
    }
}
