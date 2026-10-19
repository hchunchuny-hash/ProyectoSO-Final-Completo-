<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Simulacion extends Model
{
    protected $table = 'simulaciones';

    protected $fillable = ['user_id', 'algoritmo', 'quantum', 'procesos', 'resultado', 'prom_espera', 'prom_retorno'];

    protected function casts(): array
    {
        return ['procesos' => 'array', 'resultado' => 'array'];
    }
}
