<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codigos_recuperacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            // Huella HMAC del código; el código en claro nunca se guarda.
            $table->string('codigo_hash', 64);
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamp('expira_at');
            $table->timestamp('usado_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('permite_recuperacion')->default(true);
        });

        // El principal y las cuentas de demostración no usan recuperación por correo.
        DB::table('users')
            ->where('es_principal', true)
            ->orWhere('email', 'like', '%@sysmonitor.local')
            ->update(['permite_recuperacion' => false]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permite_recuperacion');
        });
        Schema::dropIfExists('codigos_recuperacion');
    }
};
