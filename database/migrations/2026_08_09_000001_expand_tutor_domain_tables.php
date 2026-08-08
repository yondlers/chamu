<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('tutor_statuses')->insert([
            ['name' => 'pending', 'description' => 'Tutor application awaiting review.', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'blocked', 'description' => 'Tutor is blocked from receiving bookings.', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'flagged', 'description' => 'Tutor is flagged for moderation.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::create('tutor_booking_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        DB::table('tutor_booking_statuses')->insert([
            ['name' => 'pending', 'description' => 'Booking requested and awaiting confirmation.', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'confirmed', 'description' => 'Booking confirmed by tutor or platform.', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'cancelled', 'description' => 'Booking cancelled.', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'completed', 'description' => 'Booking completed.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('tutor_applications', function (Blueprint $table) {
            $table->foreignId('tutor_status_id')->nullable()->after('status')->constrained('tutor_statuses')->nullOnDelete();
            $table->foreignId('university_id')->nullable()->after('attended_university')->constrained()->nullOnDelete();
            $table->string('university_other')->nullable()->after('university');
            $table->foreignId('qualification_id')->nullable()->after('university_other')->constrained()->nullOnDelete();
            $table->string('qualification_other')->nullable()->after('programme');
            $table->string('high_school_syllabus_other')->nullable()->after('high_school_syllabus');
            $table->string('heard_from_other')->nullable()->after('heard_from');
            $table->decimal('average_rating', 3, 2)->nullable()->after('accept_terms');
            $table->unsignedInteger('reviews_count')->default(0)->after('average_rating');
        });

        Schema::table('tutor_application_subjects', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->after('tutor_application_id')->constrained()->nullOnDelete();
            $table->string('subject_other')->nullable()->after('subject_name');
        });

        Schema::create('tutor_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_other')->nullable();
            $table->unsignedTinyInteger('mark');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('level')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tutor_application_id', 'subject_id'], 'tutor_marks_app_subject_idx');
        });

        Schema::create('tutor_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_application_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index(['tutor_application_id', 'day_of_week'], 'tutor_avail_app_day_idx');
        });

        Schema::create('tutor_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_other')->nullable();
            $table->foreignId('tutor_availability_id')->nullable()->constrained('tutor_availabilities')->nullOnDelete();
            $table->foreignId('tutor_booking_status_id')->constrained('tutor_booking_statuses')->cascadeOnDelete();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['tutor_application_id', 'booking_date'], 'tutor_bookings_app_date_idx');
            $table->index(['learner_user_id', 'booking_date'], 'tutor_bookings_learner_date_idx');
        });

        Schema::create('tutor_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tutor_booking_id')->nullable()->constrained('tutor_bookings')->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('is_visible')->default(false);
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->unique(['tutor_application_id', 'reviewer_user_id', 'tutor_booking_id'], 'tutor_reviews_unique_review_idx');
            $table->index(['tutor_application_id', 'is_visible'], 'tutor_reviews_visibility_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_reviews');
        Schema::dropIfExists('tutor_bookings');
        Schema::dropIfExists('tutor_availabilities');
        Schema::dropIfExists('tutor_marks');

        Schema::table('tutor_application_subjects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subject_id');
            $table->dropColumn('subject_other');
        });

        Schema::table('tutor_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tutor_status_id');
            $table->dropConstrainedForeignId('university_id');
            $table->dropConstrainedForeignId('qualification_id');
            $table->dropColumn([
                'university_other',
                'qualification_other',
                'high_school_syllabus_other',
                'heard_from_other',
                'average_rating',
                'reviews_count',
            ]);
        });

        Schema::dropIfExists('tutor_booking_statuses');
        Schema::dropIfExists('tutor_statuses');
    }
};
