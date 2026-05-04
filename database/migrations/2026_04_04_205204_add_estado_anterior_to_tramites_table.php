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
        Schema::table('tramites', function (Blueprint $table) {
            $table->unsignedBigInteger('estado_anterior_id')->nullable()->after('estado_id');
            $table->foreign('estado_anterior_id')->references('id')->on('tramite_estados')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            $table->dropForeign(['estado_anterior_id']);
            $table->dropColumn('estado_anterior_id');
        });
    }
};
