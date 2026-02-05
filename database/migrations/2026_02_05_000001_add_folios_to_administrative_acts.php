<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrative_acts', function (Blueprint $table) {
            $table->unsignedInteger('folios')->nullable()->after('attachments');
        });
    }

    public function down(): void
    {
        Schema::table('administrative_acts', function (Blueprint $table) {
            $table->dropColumn('folios');
        });
    }
};
