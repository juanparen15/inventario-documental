<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Modificar document_types: cambiar documentary_class_id por documentary_subseries_id
        Schema::table('document_types', function (Blueprint $table) {
            // Primero eliminar la FK existente
            $table->dropForeign(['documentary_class_id']);
            $table->dropColumn('documentary_class_id');
        });

        Schema::table('document_types', function (Blueprint $table) {
            // Agregar nueva FK a subseries (nullable para permitir tipos genéricos)
            $table->foreignId('documentary_subseries_id')->nullable()->after('name')->constrained('documentary_subseries')->nullOnDelete();
        });

        // 2. Eliminar columnas innecesarias de inventory_records
        Schema::table('inventory_records', function (Blueprint $table) {
            // Eliminar FKs primero
            $table->dropForeign(['documentary_class_id']);
            $table->dropForeign(['process_type_id']);
            $table->dropForeign(['validity_status_id']);
            $table->dropForeign(['document_purpose_id']);

            // Eliminar columnas
            $table->dropColumn([
                'documentary_class_id',
                'process_type_id',
                'validity_status_id',
                'document_purpose_id',
            ]);
        });

        // 3. Eliminar tablas innecesarias (en orden por dependencias)
        Schema::dropIfExists('documentary_classes');
        Schema::dropIfExists('process_types');
        Schema::dropIfExists('validity_statuses');
        Schema::dropIfExists('document_purposes');
    }

    public function down(): void
    {
        // Recrear tablas eliminadas
        Schema::create('document_purposes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('validity_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('process_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('documentary_classes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->foreignId('documentary_subseries_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Restaurar columnas en inventory_records
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->foreignId('documentary_class_id')->nullable()->constrained('documentary_classes');
            $table->foreignId('process_type_id')->nullable()->constrained('process_types');
            $table->foreignId('validity_status_id')->nullable()->constrained('validity_statuses');
            $table->foreignId('document_purpose_id')->nullable()->constrained('document_purposes');
        });

        // Restaurar document_types
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropForeign(['documentary_subseries_id']);
            $table->dropColumn('documentary_subseries_id');
        });

        Schema::table('document_types', function (Blueprint $table) {
            $table->foreignId('documentary_class_id')->constrained()->cascadeOnDelete();
        });
    }
};
