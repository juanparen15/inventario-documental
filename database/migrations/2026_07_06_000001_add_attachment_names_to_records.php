<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda el mapa «nombre de archivo almacenado → nombre original» para poder
     * descargar los adjuntos con su nombre original, aunque en disco se guarden
     * con un nombre saneado.
     */
    public function up(): void
    {
        Schema::table('administrative_acts', function (Blueprint $table) {
            $table->json('attachment_names')->nullable()->after('attachments');
            $table->json('confidential_attachment_names')->nullable()->after('confidential_attachments');
        });

        Schema::table('inventory_records', function (Blueprint $table) {
            $table->json('attachment_names')->nullable()->after('attachments');
        });
    }

    public function down(): void
    {
        Schema::table('administrative_acts', function (Blueprint $table) {
            $table->dropColumn(['attachment_names', 'confidential_attachment_names']);
        });

        Schema::table('inventory_records', function (Blueprint $table) {
            $table->dropColumn('attachment_names');
        });
    }
};
