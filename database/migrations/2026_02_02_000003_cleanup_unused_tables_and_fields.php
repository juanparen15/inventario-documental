<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Eliminar FK de project_id en inventory_records
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });

        // 2. Eliminar FK de document_type_id en inventory_records
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->dropForeign(['document_type_id']);
            $table->dropColumn('document_type_id');
        });

        // 3. Eliminar tablas innecesarias
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('projects');
    }

    public function down(): void
    {
        // Recrear tabla projects
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Recrear tabla document_types
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->string('name');
            $table->foreignId('documentary_subseries_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Restaurar columnas en inventory_records
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained('projects');
            $table->foreignId('document_type_id')->nullable()->constrained('document_types');
        });
    }
};
