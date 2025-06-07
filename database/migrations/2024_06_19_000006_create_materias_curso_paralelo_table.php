<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materia_curso_paralelo', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('idCursoParalelo');
            $table->unsignedSmallInteger('idMateria');
            $table->timestamps();

            $table->foreign('idCursoParalelo')
                  ->references('idCursoParalelo')
                  ->on('curso_paralelo')
                  ->onDelete('cascade');
            $table->foreign('idMateria')
                  ->references('idMateria')
                  ->on('materia')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materia_curso_paralelo');
    }
};
