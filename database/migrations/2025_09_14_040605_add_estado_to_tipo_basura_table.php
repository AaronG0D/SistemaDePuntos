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
        Schema::table('tipoBasura', function (Blueprint $table) {
            if (!Schema::hasColumn('tipoBasura', 'estado')) {
                $table->boolean('estado')->default(true);
            }
        
            if (!Schema::hasColumn('tipoBasura', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipoBasura', function (Blueprint $table) {
            $table->dropColumn('estado');
            if (Schema::hasColumn('tipoBasura', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
