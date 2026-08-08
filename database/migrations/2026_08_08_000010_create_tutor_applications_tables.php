<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->string('profile_image_path')->nullable();
            $table->string('headline')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->boolean('whatsapp_same_as_phone')->default(true);
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->json('languages')->nullable();
            $table->string('high_school_syllabus')->nullable();
            $table->boolean('attended_university')->default(false);
            $table->boolean('graduated')->default(false);
            $table->string('university')->nullable();
            $table->string('programme')->nullable();
            $table->string('specialization')->nullable();
            $table->text('tutoring_bio')->nullable();
            $table->text('tutoring_experience')->nullable();
            $table->string('tutoring_style')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->json('teaching_modes')->nullable();
            $table->string('heard_from')->nullable();
            $table->boolean('accept_terms')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tutor_application_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_application_id')->constrained()->cascadeOnDelete();
            $table->string('subject_name');
            $table->decimal('hourly_rate', 10, 2);
            $table->string('level')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tutor_application_id', 'subject_name'], 'tutor_app_subjects_app_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_application_subjects');
        Schema::dropIfExists('tutor_applications');
    }
};
