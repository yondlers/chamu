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
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('curriculum_id')->nullable()->constrained('curriculums')->nullOnDelete();
            $table->string('title');
            $table->string('mode')->nullable();
            $table->string('paper_type')->nullable();
            $table->string('quiz_type')->nullable();
            $table->string('source')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('time_limit_minutes')->nullable();
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('total_marks')->default(0);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('show_answers_immediately')->default(false);
            $table->boolean('randomize_questions')->default(false);
            $table->boolean('randomize_options')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
