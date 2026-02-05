<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('document_number')->nullable()->after('phone');
            $table->foreignId('organizational_unit_id')->nullable()->after('document_number')->constrained('organizational_units')->nullOnDelete();
            $table->string('avatar')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organizational_unit_id']);
            $table->dropColumn(['last_name', 'phone', 'document_number', 'organizational_unit_id', 'avatar']);
        });
    }
};
