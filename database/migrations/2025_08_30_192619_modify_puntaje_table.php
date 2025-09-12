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
        // Primero, creamos una tabla temporal para respaldar los datos existentes
        // No es necesario respaldar los datos al usar migrate:fresh
        // El bloque de respaldo se puede eliminar o comentar para evitar errores.
        /* if (Schema::hasTable('puntaje')) {
            // Lógica de respaldo anterior...
        } */
        
        // Eliminamos la tabla existente
        Schema::dropIfExists('puntaje');
        
        // Creamos la nueva tabla con la estructura simplificada
        Schema::create('puntaje', function (Blueprint $table) {
            $table->increments('idPuntaje');
            
            // Relación con el estudiante
            $table->unsignedSmallInteger('idUser');
            
            // Puntos asignados
            $table->integer('puntos');
            
            // Relación con el período académico
            $table->unsignedInteger('idPeriodo');
            
            // Fecha de asignación
            $table->dateTime('fechaAsignacion')->useCurrent();
            
            // Comentario opcional
            $table->text('comentario')->nullable();
            
            // Estado del puntaje
            $table->enum('estado', ['activo', 'asignado', 'acumulado'])->default('activo');
            
            // Fecha de acumulación (si aplica)
            $table->dateTime('fechaAcumulacion')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Claves foráneas
            $table->foreign('idUser')
                  ->references('id')
                  ->on('usuario')
                  ->onDelete('cascade');
                  
            $table->foreign('idPeriodo')
                  ->references('idPeriodo')
                  ->on('periodos_academicos')
                  ->onDelete('restrict');
            
            // Índices para mejorar el rendimiento de las consultas
            $table->index(['idUser', 'idPeriodo']);
            $table->index('fechaAsignacion');
            $table->index('estado');
            $table->index('idPeriodo');
        });
        
        // No hay datos de respaldo para migrar con migrate:fresh
        /* if (Schema::hasTable('puntaje_backup')) {
            // Lógica de migración de respaldo anterior...
            Schema::dropIfExists('puntaje_backup');
        } */
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // En caso de rollback, volvemos a crear la tabla original
        if (Schema::hasTable('puntaje_backup')) {
            Schema::dropIfExists('puntaje');
            
            Schema::create('puntaje', function (Blueprint $table) {
                $table->increments('idPuntaje');
                $table->unsignedSmallInteger('idUser')->unique();
                $table->integer('puntajeTotal')->default(0);
                $table->timestamp('fechaActualizacion')->useCurrent()->useCurrentOnUpdate();
                $table->timestamps();
            });
            
            // Restauramos los datos de respaldo
            \DB::statement('INSERT INTO puntaje SELECT * FROM puntaje_backup');
            
            // Eliminamos la tabla de respaldo
            Schema::dropIfExists('puntaje_backup');
        }
    }
};
