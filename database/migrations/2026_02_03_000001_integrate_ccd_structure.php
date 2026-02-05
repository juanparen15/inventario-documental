<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop UNIQUE constraint on 'code' in documentary_series
        Schema::table('documentary_series', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        // 2. Drop UNIQUE constraint on 'code' in documentary_subseries
        Schema::table('documentary_subseries', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        // 3. Mark all existing series and subseries as inactive
        DB::table('documentary_series')->update(['is_active' => false]);
        DB::table('documentary_subseries')->update(['is_active' => false]);

        // 4. Create ccd_entries table (maps which series/subseries belong to each dependency)
        Schema::create('ccd_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizational_unit_id')->constrained('organizational_units')->cascadeOnDelete();
            $table->foreignId('documentary_series_id')->constrained('documentary_series')->cascadeOnDelete();
            $table->foreignId('documentary_subseries_id')->nullable()->constrained('documentary_subseries')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['organizational_unit_id', 'documentary_series_id', 'documentary_subseries_id'],
                'ccd_entries_unit_series_subseries_unique'
            );
        });

        // 5. Add documentary_series_id and documentary_subseries_id to administrative_acts
        Schema::table('administrative_acts', function (Blueprint $table) {
            $table->foreignId('documentary_series_id')->nullable()->after('act_classification_id')
                ->constrained('documentary_series')->nullOnDelete();
            $table->foreignId('documentary_subseries_id')->nullable()->after('documentary_series_id')
                ->constrained('documentary_subseries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Remove columns from administrative_acts
        Schema::table('administrative_acts', function (Blueprint $table) {
            $table->dropForeign(['documentary_series_id']);
            $table->dropForeign(['documentary_subseries_id']);
            $table->dropColumn(['documentary_series_id', 'documentary_subseries_id']);
        });

        // Drop ccd_entries table
        Schema::dropIfExists('ccd_entries');

        // Re-activate series/subseries
        DB::table('documentary_series')->update(['is_active' => true]);
        DB::table('documentary_subseries')->update(['is_active' => true]);

        // Restore UNIQUE constraints
        Schema::table('documentary_series', function (Blueprint $table) {
            $table->unique('code');
        });

        Schema::table('documentary_subseries', function (Blueprint $table) {
            $table->unique('code');
        });
    }
};
