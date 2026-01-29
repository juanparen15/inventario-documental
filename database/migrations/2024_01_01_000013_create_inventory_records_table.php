<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_records', function (Blueprint $table) {
            $table->id();

            // Clasificación documental
            $table->foreignId('organizational_unit_id')->constrained('organizational_units');
            $table->foreignId('documentary_series_id')->constrained('documentary_series');
            $table->foreignId('documentary_subseries_id')->nullable()->constrained('documentary_subseries');
            $table->foreignId('documentary_class_id')->nullable()->constrained('documentary_classes');
            $table->foreignId('document_type_id')->nullable()->constrained('document_types');

            // Descripción del documento
            $table->string('title');
            $table->text('description')->nullable();

            // Fechas extremas
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Ubicación física
            $table->string('box')->nullable();        // Caja
            $table->string('folder')->nullable();     // Carpeta
            $table->string('volume')->nullable();     // Tomo
            $table->integer('folios')->nullable();    // Número de folios

            // Catálogos
            $table->foreignId('storage_medium_id')->nullable()->constrained('storage_mediums');     // Soporte
            $table->foreignId('document_purpose_id')->nullable()->constrained('document_purposes');   // Objeto
            $table->foreignId('process_type_id')->nullable()->constrained('process_types');       // Tipo proceso
            $table->foreignId('validity_status_id')->nullable()->constrained('validity_statuses');    // Estado vigencia
            $table->foreignId('priority_level_id')->nullable()->constrained('priority_levels');     // Prioridad
            $table->foreignId('project_id')->nullable()->constrained('projects');            // Proyecto

            // Metadatos
            $table->text('notes')->nullable();
            $table->string('reference_code')->unique()->nullable(); // Código único de referencia

            // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Índices para búsqueda
            $table->index(['start_date', 'end_date']);
            $table->index('box');
            $table->index('reference_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_records');
    }
};
