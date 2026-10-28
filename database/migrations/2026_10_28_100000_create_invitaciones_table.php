<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            // Solo se guarda la huella (sha256) del código, nunca el código.
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expira_at');
            $table->timestamp('usada_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitaciones');
    }
};
