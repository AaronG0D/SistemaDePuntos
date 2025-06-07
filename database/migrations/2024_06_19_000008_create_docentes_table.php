<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docente', function (Blueprint $table) {
            $table->smallIncrements('idDocente');
            $table->unsignedSmallInteger('idUser')->unique();
            $table->timestamps();

            $table->foreign('idUser')
                  ->references('id')
                  ->on('usuario')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docente');
    }
};
