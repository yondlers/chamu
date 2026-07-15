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
        Schema::create('exam_session_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('past_paper_question_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('question_order')->default(0);
            $table->unsignedInteger('marks')->default(0);
            $table->longText('selected_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->unsignedInteger('time_taken_seconds')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_session_questions');
    }
};
