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
        Schema::table('qualifications', function (Blueprint $table) {
            $table->decimal('admission_score_required', 8, 2)->nullable()->after('aggregate_average_required');
            $table->string('minimum_pass_type')->nullable()->after('admission_score_required');
            $table->foreignId('required_grade_id')->nullable()->after('nqf_level_id')->constrained('grades')->nullOnDelete();
        });

        Schema::table('qualification_subject_requirements', function (Blueprint $table) {
            $table->foreignId('grade_id')->nullable()->after('subject_id')->constrained('grades')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qualification_subject_requirements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grade_id');
        });

        Schema::table('qualifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('required_grade_id');
            $table->dropColumn(['admission_score_required', 'minimum_pass_type']);
        });
    }
};
