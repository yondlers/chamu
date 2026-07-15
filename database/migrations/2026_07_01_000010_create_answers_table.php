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
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->longText('correct_answer')->nullable();
            $table->json('accepted_answers')->nullable();
            $table->longText('explanation')->nullable();
            $table->string('answer_type')->nullable();
            $table->boolean('is_case_sensitive')->default(false);
            $table->boolean('requires_exact_match')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
