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
        Schema::create('university_admission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('university_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('qualification_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_default')->default(false);
            $table->json('overrides')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['university_id', 'faculty_id', 'qualification_id'], 'university_admission_rules_scope_index');
            $table->index(['university_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('university_admission_rules');
    }
};
