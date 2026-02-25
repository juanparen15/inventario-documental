<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            // Cambia de integer a string para permitir rangos como "1-200" o "200-300"
            $table->string('folios', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->integer('folios')->nullable()->change();
        });
    }
};
