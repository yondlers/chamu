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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            $table->boolean('active')->default(true);

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // FK to users table

            // Basic Information

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');

            $table->string('id_number');
            $table->string('passport_number')->nullable()->unique();

            $table->date('date_of_birth'); // Stored as a date type for easier calculations
            $table->string('email');
            $table->string('contact_number')->nullable();
            $table->string('work_number')->nullable();

            $table->foreignId('gender_id')->nullable()->constrained('genders')->onDelete('cascade'); // FK to teams table
            $table->foreignId('marital_status_id')->nullable()->constrained('marital_statuses')->onDelete('cascade'); // FK to teams table
            $table->foreignId('ethnicity_id')->nullable()->constrained('ethnicities')->onDelete('cascade'); // FK to teams table
            $table->foreignId('language_id')->nullable()->constrained('languages')->onDelete('cascade'); // FK to teams table
            $table->foreignId('suburb_id')->nullable()->constrained('suburbs')->onDelete('cascade');
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('cascade');
            $table->foreignId('province_id')->nullable()->constrained('provinces')->onDelete('cascade');
            $table->foreignId('country_id')->nullable()->constrained('countries')->onDelete('cascade');

            // Address Information
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('postal_code');

            // Employment and Financial Information
            $table->string('occupation');
            $table->string('employer')->nullable();
            $table->decimal('monthly_income', 10, 2)->nullable(); // Optional: Track tenant's income for affordability checks

            $table->boolean('credit_check_passed')->default(false); // Indicates if a credit check was successfully passed
            $table->text('credit_check_report')->nullable(); // Optional: Store details of credit check results

            // Lease and Occupancy Information
            $table->integer('number_of_occupancies')->default(1); // Total number of people occupying the unit
            $table->boolean('has_pets')->default(false); // Indicates if the tenant has pets
            $table->string('special_requirements')->nullable(); // Any special requirements or notes

            // Emergency Contact Information
            $table->string('emergency_name')->nullable();
            $table->string('emergency_relationship')->nullable();
            $table->string('emergency_number')->nullable();

            // Legal and Other Details
            $table->boolean('blacklisted')->default(false); // Indicates if the tenant is blacklisted for any reason

            // Document Information
            $table->string('file_prefix')->nullable(); // Generated
            $table->string('id_document_file_name')->nullable(); // Path to stored ID document
            $table->string('bank_statements_file_name')->nullable(); // Path to stored bank statements
            $table->string('proof_of_income_file_name')->nullable(); // Path to proof of income document (optional)
            $table->string('credit_report_file_name')->nullable(); // Path to proof of income document (optional)

            $table->timestamps();
            $table->softDeletes(); // Adds deleted_at column for soft deletes

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
