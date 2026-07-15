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
        Schema::create('past_paper_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('past_paper_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained('topic_skills')->nullOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paper_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->string('question_number')->nullable();
            $table->longText('question');
            $table->text('hint')->nullable();
            $table->longText('answer')->nullable();
            $table->json('options')->nullable();
            $table->string('question_type')->nullable();
            $table->string('answer_type')->nullable();
            $table->unsignedInteger('marks')->default(0);
            $table->string('difficulty')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('past_paper_questions');
    }
};
