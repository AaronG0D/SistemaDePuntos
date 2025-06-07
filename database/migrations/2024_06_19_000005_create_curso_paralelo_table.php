<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curso_paralelo', function (Blueprint $table) {
            $table->smallIncrements('idCursoParalelo');
            $table->unsignedSmallInteger('idCurso');
            $table->unsignedSmallInteger('idParalelo');
            $table->timestamps();

            $table->foreign('idCurso')
                  ->references('idCurso')
                  ->on('curso')
                  ->onDelete('cascade');

            $table->foreign('idParalelo')
                  ->references('idParalelo')
                  ->on('paralelo')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curso_paralelo');
    }
};
