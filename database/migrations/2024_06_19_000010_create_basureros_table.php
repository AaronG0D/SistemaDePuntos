<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basurero', function (Blueprint $table) {
            $table->tinyIncrements('idBasurero');
            $table->string('ubicacion', 255);
            $table->text('descripcion')->nullable();
            $table->tinyInteger('estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basurero');
    }
};
