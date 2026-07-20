<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_application_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('applicant_phone')->nullable();
            $table->text('applicant_postal_address')->nullable();
            $table->string('study_level')->nullable();
            $table->string('institution')->nullable();
            $table->string('qualification')->nullable();
            $table->string('current_year')->nullable();
            $table->text('funding_need')->nullable();
            $table->string('household_income')->nullable();
            $table->boolean('sassa_recipient')->default(false);
            $table->json('special_circumstances')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_application_profiles');
    }
};
