<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// role, estado, es_principal y creado_por NO son asignables en masa:
// los fija el código, nunca un formulario.
#[Fillable([
    'name', 'email', 'password',
    'telefono', 'institucion', 'motivo_acceso', 'notas_admin',
    'vigente_desde', 'vigente_hasta',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'vigente_desde'     => 'date',
            'vigente_hasta'     => 'date',
            'es_principal'      => 'boolean',
        ];
    }

    public function esAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function esPrincipal(): bool
    {
        return (bool) $this->es_principal;
    }

    public function estaVencido(): bool
    {
        return $this->vigente_hasta !== null
            && $this->vigente_hasta->toDateString() < now('America/Guatemala')->toDateString();
    }

    public function aunNoVigente(): bool
    {
        return $this->vigente_desde !== null
            && $this->vigente_desde->toDateString() > now('America/Guatemala')->toDateString();
    }

    /** Estado que se muestra: activo, inactivo, pendiente o vencido. */
    public function estadoEfectivo(): string
    {
        if (! $this->esPrincipal() && $this->estado === 'activo' && $this->estaVencido()) {
            return 'vencido';
        }
        return $this->estado;
    }

    /** El administrador principal nunca queda bloqueado por estado o vigencia. */
    public function puedeAcceder(): bool
    {
        if ($this->esPrincipal()) {
            return true;
        }
        return $this->estado === 'activo' && ! $this->estaVencido() && ! $this->aunNoVigente();
    }

    /** ¿Puede usar «¿Olvidaste tu contraseña?»? El principal se recupera por consola. */
    public function puedeRecuperar(): bool
    {
        return ! $this->esPrincipal()
            && (bool) $this->permite_recuperacion
            && $this->estado === 'activo'
            && $this->puedeAcceder();
    }
}
