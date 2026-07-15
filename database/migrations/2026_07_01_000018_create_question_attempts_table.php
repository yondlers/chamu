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
        Schema::create('question_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sub_question_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('past_paper_question_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exam_session_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('selected_answer')->nullable();
            $table->longText('correct_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('marks_awarded', 6, 2)->default(0);
            $table->unsignedInteger('time_taken_seconds')->nullable();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_attempts');
    }
};
