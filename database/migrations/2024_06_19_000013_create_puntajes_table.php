<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puntaje', function (Blueprint $table) {
            $table->increments('idPuntaje');
            $table->unsignedSmallInteger('idUser')->unique();
            $table->integer('puntajeTotal')->default(0);
            $table->timestamp('fechaActualizacion')->useCurrent()->useCurrentOnUpdate();
            $table->timestamps();

            $table->foreign('idUser')
                  ->references('id')
                  ->on('usuario')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puntaje');
    }
};
