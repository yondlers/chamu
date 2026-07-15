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
        Schema::create('charade_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charade_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained('topic_skills')->nullOnDelete();
            $table->string('word');
            $table->text('clue')->nullable();
            $table->string('answer')->nullable();
            $table->string('difficulty')->nullable();
            $table->unsignedInteger('points')->default(0);
            $table->unsignedInteger('time_limit_seconds')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charade_cards');
    }
};
