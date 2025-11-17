<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposito', function (Blueprint $table) {
            // ID del período académico (nullable para datos antiguos)
            $table->unsignedInteger('idPeriodo')->nullable()->after('idTipoBasura');
            // Puntos del tipo de basura (snapshot en el momento del depósito)
            $table->integer('puntos')->nullable()->after('idPeriodo');

            // Índices y llave foránea
            $table->index('idPeriodo');
            $table->foreign('idPeriodo')
                ->references('idPeriodo')
                ->on('periodos_academicos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deposito', function (Blueprint $table) {
            $table->dropForeign(['idPeriodo']);
            $table->dropIndex(['idPeriodo']);
            $table->dropColumn('puntos');
            $table->dropColumn('idPeriodo');
        });
    }
};