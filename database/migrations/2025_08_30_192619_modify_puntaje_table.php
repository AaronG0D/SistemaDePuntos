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
        if (Schema::hasTable('puntaje')) {
            Schema::create('puntaje_backup', function (Blueprint $table) {
                $table->increments('idPuntaje');
                $table->unsignedSmallInteger('idUser');
                $table->integer('puntajeTotal');
                $table->timestamps();
            });
            
            // Copiamos los datos a la tabla temporal
            \DB::statement('INSERT INTO puntaje_backup SELECT * FROM puntaje');
        }
        
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
        
        // Si había datos de respaldo, podemos intentar migrarlos
        if (Schema::hasTable('puntaje_backup')) {
            // Aquí puedes agregar lógica para migrar los datos antiguos si es necesario
            // Por ejemplo, asignarlos a una materia por defecto o a un docente específico
            
            // Finalmente eliminamos la tabla de respaldo
            Schema::dropIfExists('puntaje_backup');
        }
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
