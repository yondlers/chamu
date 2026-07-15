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
        Schema::create('charade_session_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charade_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charade_card_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('card_order')->default(0);
            $table->string('guessed_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->unsignedInteger('points_awarded')->default(0);
            $table->unsignedInteger('time_taken_seconds')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charade_session_cards');
    }
};
