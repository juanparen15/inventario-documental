<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // inventory_records: created_by, updated_by
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // attachments: uploaded_by
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });

        // administrative_acts: created_by, updated_by
        Schema::table('administrative_acts', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->foreign('uploaded_by')->references('id')->on('users');
        });

        Schema::table('administrative_acts', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }
};
