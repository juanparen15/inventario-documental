<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía la columna subject de VARCHAR(255) a TEXT
 * para soportar objetos/asuntos de actos administrativos largos.
 *
 * El formulario permitía hasta 1000 caracteres pero la columna
 * sólo aceptaba 255, produciendo SQLSTATE[22001] en producción.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrative_acts', function (Blueprint $table) {
            $table->text('subject')->change();              // objeto del acto — sin límite
            $table->string('slug', 500)->unique()->change(); // slug generado del subject — puede ser largo
        });
    }

    public function down(): void
    {
        Schema::table('administrative_acts', function (Blueprint $table) {
            $table->string('subject', 255)->change();
            $table->string('slug', 255)->unique()->change();
        });
    }
};
