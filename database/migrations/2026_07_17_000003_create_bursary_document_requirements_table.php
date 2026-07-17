<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bursary_document_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bursary_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('accepts_multiple')->default(false);
            $table->string('requirement_group')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['bursary_id', 'key']);
            $table->index(['bursary_id', 'requirement_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bursary_document_requirements');
    }
};
