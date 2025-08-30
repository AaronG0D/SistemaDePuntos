<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('asignaciones_puntaje', function (Blueprint $table) {
            $table->increments('idAsignacion');
            
            // Relación con el puntaje
            $table->unsignedInteger('idPuntaje');
            
            // Relación con el período académico (redundante pero útil para consultas)
            $table->unsignedInteger('idPeriodo');
            
            // Relación con el docente
            $table->unsignedSmallInteger('idDocente');
            
            // Relación con la materia
            $table->unsignedSmallInteger('idMateria');
            
            // Fecha de asignación (puede ser diferente a la del puntaje)
            $table->dateTime('fecha_asignacion')->useCurrent();
            
            // Porcentaje del puntaje asignado a esta materia (opcional, para dividir un puntaje)
            $table->decimal('porcentaje', 5, 2)->default(100);
            
            // Puntos asignados (copia para facilitar consultas)
            $table->integer('puntos');
            
            // Comentario opcional
            $table->text('comentario')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Claves foráneas
            $table->foreign('idPuntaje')
                  ->references('idPuntaje')
                  ->on('puntaje')
                  ->onDelete('cascade');
                  
            $table->foreign('idPeriodo')
                  ->references('idPeriodo')
                  ->on('periodos_academicos')
                  ->onDelete('restrict');
                  
            $table->foreign('idDocente')
                  ->references('idDocente')
                  ->on('docente')
                  ->onDelete('cascade');
                  
            $table->foreign('idMateria')
                  ->references('idMateria')
                  ->on('materia')
                  ->onDelete('cascade');
            
            // Restricción única para evitar duplicados
            $table->unique(['idPuntaje', 'idMateria', 'idDocente'], 'unique_asignacion');
            
            // Índices para mejorar el rendimiento
            $table->index(['idPuntaje', 'idMateria', 'idDocente']);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('asignaciones_puntaje');
    }
};
