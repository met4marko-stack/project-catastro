<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vias', function (Blueprint $table) {
            $table->foreignId('tipo_via_id')->nullable()->after('id')->constrained('tipo_vias');
            $table->string('nombre_especifico')->nullable()->after('tipo_via_id');
        });
    }

    public function down(): void
    {
        Schema::table('vias', function (Blueprint $table) {
            $table->dropForeign(['tipo_via_id']);
            $table->dropColumn(['tipo_via_id', 'nombre_especifico']);
        });
    }
};