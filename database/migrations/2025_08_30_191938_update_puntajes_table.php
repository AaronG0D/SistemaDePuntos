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
        // Primero, eliminamos la tabla antigua si existe
        Schema::dropIfExists('puntaje');
        
        // Creamos la nueva tabla de puntajes
        Schema::create('puntaje', function (Blueprint $table) {
            $table->increments('idPuntaje');
            
            // Relación con el estudiante
            $table->unsignedSmallInteger('idUser');
            
            // Relación con la materia
            $table->unsignedSmallInteger('idMateria');
            
            // Relación con el docente que asigna los puntos
            $table->unsignedSmallInteger('idDocente');
            
            // Puntos asignados
            $table->integer('puntos');
            
            // Fecha de asignación
            $table->dateTime('fechaAsignacion')->useCurrent();
            
            // Comentario opcional
            $table->text('comentario')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Claves foráneas
            $table->foreign('idUser')
                  ->references('id')
                  ->on('usuario')
                  ->onDelete('cascade');
                  
            $table->foreign('idMateria')
                  ->references('idMateria')
                  ->on('materia')
                  ->onDelete('cascade');
                  
            $table->foreign('idDocente')
                  ->references('idDocente')
                  ->on('docente')
                  ->onDelete('cascade');
            
            // Índices para mejorar el rendimiento de las consultas
            $table->index(['idUser', 'idMateria']);
            $table->index('fechaAsignacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Eliminamos la tabla si existe
        Schema::dropIfExists('puntaje');
        
        // Opcional: Podrías querer recrear la tabla original aquí si es necesario
        // para el rollback completo
    }
};
