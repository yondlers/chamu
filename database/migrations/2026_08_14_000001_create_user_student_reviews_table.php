<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_student_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_id')->nullable()->constrained('curriculums')->nullOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->string('snapshot_hash', 64);
            $table->string('status', 40)->default('generating');
            $table->text('review_text')->nullable();
            $table->unsignedTinyInteger('subject_count')->default(0);
            $table->unsignedTinyInteger('marked_subject_count')->default(0);
            $table->unsignedSmallInteger('aps_total')->default(0);
            $table->decimal('average_mark', 5, 2)->nullable();
            $table->unsignedInteger('qualified_count')->default(0);
            $table->string('provider', 40)->nullable();
            $table->string('model', 80)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'snapshot_hash']);
            $table->index(['user_id', 'grade_id', 'term_id']);
            $table->index(['user_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_student_reviews');
    }
};
