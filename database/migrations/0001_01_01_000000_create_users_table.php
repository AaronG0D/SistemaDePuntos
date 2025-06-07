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
        Schema::create('usuario', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('nombres', 100);
            $table->string('primerApellido', 100);
            $table->string('segundoApellido', 100)->nullable();
            $table->string('email', 100)->unique();
            $table->enum('rol', ['estudiante', 'docente', 'administrador']);
            $table->string('qr_codigo', 255)->unique()->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedSmallInteger('user_id')->nullable()->index();
            $table->foreign('user_id')
                  ->references('id')
                  ->on('usuario')
                  ->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');;
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuario');
    }
};
