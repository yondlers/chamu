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
        Schema::create('note_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_deck_id')->constrained()->cascadeOnDelete();
            $table->text('front');
            $table->text('back');
            $table->text('hint')->nullable();
            $table->string('content_image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note_cards');
    }
};
