<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docente_materia_curso', function (Blueprint $table) {
            $table->unsignedSmallInteger('idDocente');
            $table->unsignedSmallInteger('idMateria');
            $table->unsignedSmallInteger('idCursoParalelo');
            $table->timestamps();

            $table->primary(['idDocente', 'idMateria', 'idCursoParalelo']);
            $table->foreign('idDocente')
                  ->references('idDocente')
                  ->on('docente')
                  ->onDelete('cascade');
            $table->foreign('idMateria')
                  ->references('idMateria')
                  ->on('materia')
                  ->onDelete('cascade');
            $table->foreign('idCursoParalelo')
                  ->references('idCursoParalelo')
                  ->on('curso_paralelo')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_materia_curso');
    }
};
