<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('periodos_academicos', function (Blueprint $table) {
            $table->increments('idPeriodo');
            $table->string('nombre', 50); // Ej: "Primer Bimestre 2024"
            $table->string('codigo', 10)->unique(); // Ej: "2024-1"
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('activo')->default(false);
            $table->timestamps();
            
            // Índices
            $table->index('codigo');
            $table->index('activo');
        });
        
        // Insertar períodos para el año actual
        $year = date('Y');
        $periodos = [
            ['nombre' => 'Primer Bimestre', 'codigo' => "$year-1", 'fecha_inicio' => "$year-01-01", 'fecha_fin' => "$year-03-31"],
            ['nombre' => 'Segundo Bimestre', 'codigo' => "$year-2", 'fecha_inicio' => "$year-04-01", 'fecha_fin' => "$year-06-30"],
            ['nombre' => 'Tercer Bimestre', 'codigo' => "$year-3", 'fecha_inicio' => "$year-07-01", 'fecha_fin' => "$year-09-30"],
            ['nombre' => 'Cuarto Bimestre', 'codigo' => "$year-4", 'fecha_inicio' => "$year-10-01", 'fecha_fin' => "$year-12-31"],
        ];
        
        // Activar el primer período por defecto
        $periodos[0]['activo'] = true;
        
        \DB::table('periodos_academicos')->insert($periodos);
    }

    public function down()
    {
        Schema::dropIfExists('periodos_academicos');
    }
};
