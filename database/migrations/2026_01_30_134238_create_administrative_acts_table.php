<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_acts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('organizational_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->foreignId('act_classification_id')->nullable()->constrained('act_classifications')->nullOnDelete();
            $table->string('filing_number')->nullable(); // consecutivo
            $table->date('act_date')->nullable();
            $table->string('subject'); // objeto
            $table->json('attachments')->nullable(); // pdf_acto_administrativo
            $table->string('slug')->unique();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('filing_number');
            $table->index('act_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_acts');
    }
};
