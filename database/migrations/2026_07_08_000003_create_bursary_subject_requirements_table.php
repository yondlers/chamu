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
        Schema::create('bursary_subject_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bursary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_name')->nullable();
            $table->unsignedTinyInteger('minimum_mark')->nullable();
            $table->unsignedTinyInteger('aps_level_required')->nullable();
            $table->string('requirement_type')->default('required');
            $table->string('requirement_group')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bursary_subject_requirements');
    }
};
