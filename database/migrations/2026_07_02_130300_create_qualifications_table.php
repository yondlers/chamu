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
        Schema::create('qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qualification_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('nqf_level_id')->nullable()->constrained('nqf_levels')->nullOnDelete();
            $table->string('name');
            $table->string('abbreviation')->nullable();
            $table->decimal('duration_years', 3, 1)->nullable();
            $table->unsignedTinyInteger('aps_required')->nullable();
            $table->unsignedTinyInteger('closing_month')->nullable();
            $table->unsignedTinyInteger('closing_day')->nullable();
            $table->boolean('is_selection_programme')->default(false);
            $table->text('notes')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qualifications');
    }
};
