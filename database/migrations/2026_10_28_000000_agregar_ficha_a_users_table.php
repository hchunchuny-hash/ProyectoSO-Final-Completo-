<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telefono', 30)->nullable();
            $table->string('institucion', 120)->nullable();
            $table->string('motivo_acceso', 500)->nullable();
            $table->text('notas_admin')->nullable();
            // activo | inactivo | pendiente (invitada, aún no define su contraseña)
            $table->string('estado', 20)->default('activo');
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();
            $table->boolean('es_principal')->default(false);
            $table->unsignedBigInteger('creado_por')->nullable();
        });

        // Las cuentas que ya existen quedan activas (default) y el primer
        // administrador pasa a ser el principal.
        $primerAdmin = DB::table('users')->where('role', 'admin')->orderBy('id')->value('id');
        if ($primerAdmin) {
            DB::table('users')->where('id', $primerAdmin)->update(['es_principal' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telefono', 'institucion', 'motivo_acceso', 'notas_admin',
                'estado', 'vigente_desde', 'vigente_hasta', 'es_principal', 'creado_por',
            ]);
        });
    }
};
