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
        Schema::create('admission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('score_type');
            $table->string('calculation_method');
            $table->string('score_label')->nullable();
            $table->string('score_suffix', 10)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->boolean('include_life_orientation')->default(false);
            $table->foreignId('life_orientation_subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->unsignedTinyInteger('subject_count')->nullable();
            $table->string('subject_selection_strategy')->default('all_subjects');
            $table->string('minimum_pass_type')->nullable();
            $table->json('points_scale')->nullable();
            $table->json('config')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_rules');
    }
};
