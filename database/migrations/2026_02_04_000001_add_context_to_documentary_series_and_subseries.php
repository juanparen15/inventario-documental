<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentary_series', function (Blueprint $table) {
            $table->string('context', 10)->default('ccd')->after('is_active');
            $table->index('context');
        });

        Schema::table('documentary_subseries', function (Blueprint $table) {
            $table->string('context', 10)->default('ccd')->after('is_active');
            $table->index('context');
        });

        // Backfill existing records to 'ccd'
        DB::table('documentary_series')->whereNull('context')->orWhere('context', '')->update(['context' => 'ccd']);
        DB::table('documentary_subseries')->whereNull('context')->orWhere('context', '')->update(['context' => 'ccd']);
    }

    public function down(): void
    {
        Schema::table('documentary_series', function (Blueprint $table) {
            $table->dropIndex(['context']);
            $table->dropColumn('context');
        });

        Schema::table('documentary_subseries', function (Blueprint $table) {
            $table->dropIndex(['context']);
            $table->dropColumn('context');
        });
    }
};
