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
        Schema::create('ai_explanations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('past_paper_question_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->longText('explanation')->nullable();
            $table->longText('worked_solution')->nullable();
            $table->longText('common_mistakes')->nullable();
            $table->text('memory_tip')->nullable();
            $table->string('video_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_explanations');
    }
};
