<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrative_acts', function (Blueprint $table) {
            // Almacena los umbrales de días en que ya se envió notificación de PDF pendiente.
            // Ejemplo: [15, 25, 30]
            $table->json('pdf_notified_days')->nullable()->after('folios');
        });
    }

    public function down(): void
    {
        Schema::table('administrative_acts', function (Blueprint $table) {
            $table->dropColumn('pdf_notified_days');
        });
    }
};
