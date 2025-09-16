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
        Schema::create('historial_importaciones', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('id_curso_paralelo')->unsigned();
            $table->integer('insertados')->default(0);
            $table->integer('actualizados')->default(0);
            $table->integer('omitidos')->default(0);
            $table->integer('errores_count')->default(0);
            $table->timestamps();
            
            $table->foreign('id_curso_paralelo')
                  ->references('idCursoParalelo')
                  ->on('curso_paralelo')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_importaciones');
    }
};
