<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipoBasura', function (Blueprint $table) {
            $table->tinyIncrements('idTipoBasura');
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->integer('puntos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipoBasura');
    }
};
