<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paralelo', function (Blueprint $table) {
            $table->smallIncrements('idParalelo');
            $table->string('nombre', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paralelo');
    }
};
