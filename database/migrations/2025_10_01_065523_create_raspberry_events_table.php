<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('raspberry_events', function (Blueprint $table) {
            $table->id();
            $table->string('qr_codigo')->index();
            $table->unsignedTinyInteger('idTipoBasura')->nullable()->index();
            $table->string('tipo_basura_nombre')->nullable()->index();
            $table->unsignedSmallInteger('idUser')->nullable()->index();
            $table->unsignedMediumInteger('idDeposito')->nullable()->index();
            $table->string('status')->default('pending')->index(); // pending|success|failed
            $table->string('message')->nullable();
            $table->json('meta')->nullable(); // datos extra del dispositivo: peso, sensor, etc.
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('idUser')->references('id')->on('usuario')->nullOnDelete();
            $table->foreign('idTipoBasura')->references('idTipoBasura')->on('tipoBasura')->nullOnDelete();
            $table->foreign('idDeposito')->references('idDeposito')->on('deposito')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raspberry_events');
    }
};
