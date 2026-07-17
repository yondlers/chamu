<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bursary_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bursary_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('provider_email');
            $table->string('applicant_name');
            $table->string('applicant_email');
            $table->string('applicant_phone')->nullable();
            $table->string('study_level')->nullable();
            $table->string('institution')->nullable();
            $table->string('qualification')->nullable();
            $table->string('current_year')->nullable();
            $table->text('funding_need')->nullable();
            $table->string('household_income')->nullable();
            $table->boolean('sassa_recipient')->default(false);
            $table->json('special_circumstances')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('receipt_sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'bursary_id']);
            $table->index(['bursary_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bursary_applications');
    }
};
