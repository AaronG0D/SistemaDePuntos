<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposito', function (Blueprint $table) {
            // Agregar idPeriodo
            $table->unsignedInteger('idPeriodo')
                  ->after('idTipoBasura'); // Cambia 'idTipoBasura' por la columna adecuada según tu tabla

            // Agregar puntajeTipoBasura
            $table->unsignedSmallInteger('puntajeTipoBasura')
                  ->after('idPeriodo');

            // Llave foránea
            $table->foreign('idPeriodo')
                  ->references('idPeriodo')
                  ->on('periodos_academicos')
                  ->onDelete('cascade'); // Opcional, puedes quitarlo si no quieres borrado en cascada
        });
    }

    public function down(): void
    {
        Schema::table('deposito', function (Blueprint $table) {
            $table->dropForeign(['idPeriodo']);
            $table->dropColumn(['idPeriodo', 'puntajeTipoBasura']);
        });
    }
};
