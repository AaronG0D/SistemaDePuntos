<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiante', function (Blueprint $table) {
            $table->unsignedSmallInteger('idUser')->primary();
            $table->unsignedSmallInteger('idCursoParalelo');
            $table->timestamps();

            $table->foreign('idUser')
                  ->references('id')
                  ->on('usuario')
                  ->onDelete('cascade');
            $table->foreign('idCursoParalelo')
                  ->references('idCursoParalelo')
                  ->on('curso_paralelo')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante');
    }
};
