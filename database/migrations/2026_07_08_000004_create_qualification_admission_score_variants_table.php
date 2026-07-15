<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qualification_admission_score_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qualification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('subject_name')->nullable();
            $table->unsignedTinyInteger('minimum_mark')->nullable();
            $table->unsignedTinyInteger('aps_level_required')->nullable();
            $table->decimal('admission_score_required', 8, 2);
            $table->string('label')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qualification_admission_score_variants');
    }
};
