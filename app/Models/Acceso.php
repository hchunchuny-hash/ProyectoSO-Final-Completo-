<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class Acceso extends Model
{
    protected $table = 'accesos';

    public $timestamps = false;

    protected $fillable = ['user_id', 'email', 'exito', 'motivo', 'ip', 'navegador'];

    protected function casts(): array
    {
        return [
            'exito'      => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /** Anota un intento de acceso. Si falla al guardar, no rompe el login. */
    public static function registrar(?int $userId, string $email, bool $exito, ?string $motivo = null): void
    {
        try {
            static::create([
                'user_id'   => $userId,
                'email'     => Str::limit($email, 150, ''),
                'exito'     => $exito,
                'motivo'    => $motivo,
                'ip'        => request()->ip(),
                'navegador' => Str::limit((string) request()->userAgent(), 255, ''),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
