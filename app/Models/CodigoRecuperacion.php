<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodigoRecuperacion extends Model
{
    protected $table = 'codigos_recuperacion';

    protected $fillable = ['user_id', 'codigo_hash', 'expira_at', 'ip'];

    protected function casts(): array
    {
        return [
            'expira_at' => 'datetime',
            'usado_at'  => 'datetime',
        ];
    }
}
