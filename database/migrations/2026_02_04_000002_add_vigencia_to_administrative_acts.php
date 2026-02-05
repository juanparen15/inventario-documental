<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrative_acts', function (Blueprint $table) {
            $table->unsignedSmallInteger('vigencia')->default(2026)->after('act_classification_id');
            $table->index('vigencia');
        });

        // Backfill vigencia from act_date year, or created_at year
        DB::table('administrative_acts')
            ->whereNotNull('act_date')
            ->update(['vigencia' => DB::raw('YEAR(act_date)')]);

        DB::table('administrative_acts')
            ->whereNull('act_date')
            ->update(['vigencia' => DB::raw('YEAR(created_at)')]);
    }

    public function down(): void
    {
        Schema::table('administrative_acts', function (Blueprint $table) {
            $table->dropIndex(['vigencia']);
            $table->dropColumn('vigencia');
        });
    }
};
