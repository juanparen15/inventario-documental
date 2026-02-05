<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            // Objeto del inventario (FUID)
            $table->string('inventory_purpose')->nullable()->after('organizational_unit_id')
                ->comment('Objeto: transferencias_primarias, transferencias_secundarias, valoracion_fondos, fusion_supresion, inventarios_individuales');

            // Tipo de unidad de almacenamiento (microfilm, casettes, CD, DVD, etc.)
            $table->string('storage_unit_type')->nullable()->after('folios')
                ->comment('Tipo de unidad de almacenamiento: microfilm, casette, cd, dvd, etc.');

            // Cantidad de unidades de conservacion
            $table->integer('storage_unit_quantity')->nullable()->after('storage_unit_type')
                ->comment('Cantidad de unidades de almacenamiento');

            // Indicador si tiene fecha o es S.F. (sin fecha)
            $table->boolean('has_start_date')->default(true)->after('end_date');
            $table->boolean('has_end_date')->default(true)->after('has_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->dropColumn([
                'inventory_purpose',
                'storage_unit_type',
                'storage_unit_quantity',
                'has_start_date',
                'has_end_date',
            ]);
        });
    }
};
