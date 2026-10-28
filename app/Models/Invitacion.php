<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitacion extends Model
{
    protected $table = 'invitaciones';

    protected $fillable = ['user_id', 'token_hash', 'expira_at'];

    protected function casts(): array
    {
        return [
            'expira_at' => 'datetime',
            'usada_at'  => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
