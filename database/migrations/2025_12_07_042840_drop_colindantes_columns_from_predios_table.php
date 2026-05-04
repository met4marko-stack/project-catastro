<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predios', function (Blueprint $table) {
            $table->dropColumn(['colindante_norte', 'colindante_sur', 'colindante_este', 'colindante_oeste']);
        });
    }

    public function down(): void
    {
        Schema::table('predios', function (Blueprint $table) {
            $table->string('colindante_norte')->nullable();
            $table->string('colindante_sur')->nullable();
            $table->string('colindante_este')->nullable();
            $table->string('colindante_oeste')->nullable();
        });
    }
};