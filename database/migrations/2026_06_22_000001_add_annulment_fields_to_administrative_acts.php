<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos para la anulación de registros del Sistema Unificado.
     * La anulación reutiliza el soft delete (deleted_at = fecha de anulación);
     * estas columnas guardan el motivo obligatorio y quién la realizó.
     */
    public function up(): void
    {
        Schema::table('administrative_acts', function (Blueprint $table) {
            $table->text('annulment_reason')->nullable()->after('late_upload_reason');
            $table->foreignId('annulled_by')->nullable()->after('annulment_reason')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('administrative_acts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('annulled_by');
            $table->dropColumn('annulment_reason');
        });
    }
};
