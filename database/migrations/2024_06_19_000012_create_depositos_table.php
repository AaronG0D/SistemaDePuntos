<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposito', function (Blueprint $table) {
            $table->mediumIncrements('idDeposito');
            $table->unsignedSmallInteger('idUser');
            $table->unsignedTinyInteger('idBasurero');
            $table->unsignedTinyInteger('idTipoBasura');
            $table->dateTime('fechaHora')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamps();

            $table->foreign('idUser')->references('id')->on('usuario');
            $table->foreign('idBasurero')->references('idBasurero')->on('basurero');
            $table->foreign('idTipoBasura')->references('idTipoBasura')->on('tipoBasura');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposito');
    }
};
