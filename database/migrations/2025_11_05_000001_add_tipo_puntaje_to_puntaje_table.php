<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('puntaje', function (Blueprint $table) {
            $table->enum('tipo_puntaje', ['depositos', 'extracurricular'])
                  ->default('depositos')
                  ->after('puntos')
                  ->comment('Tipo de puntaje: depositos (automático por reciclaje) o extracurricular (manual por docente)');
            
            // Agregar índice para mejorar consultas
            $table->index(['idUser', 'idPeriodo', 'tipo_puntaje']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('puntaje', function (Blueprint $table) {
            $table->dropIndex(['idUser', 'idPeriodo', 'tipo_puntaje']);
            $table->dropColumn('tipo_puntaje');
        });
    }
};
