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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();

            $table->boolean('active')->default(true);

            $table->string('unit_number')->nullable(); // Identifier for the unit (e.g., "A101")

            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();

            $table->string('postal_code');

            $table->foreignId('suburb_id')->constrained('suburbs')->onDelete('cascade'); // FK
            $table->foreignId('city_id')->constrained('cities')->onDelete('cascade'); // FK
            $table->foreignId('province_id')->constrained('provinces')->onDelete('cascade'); // FK
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade'); // FK

            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->foreignId('property_type_id')->constrained('property_types')->onDelete('cascade'); // FK to property_types
            $table->foreignId('asset_information_id')->nullable()->constrained('asset_informations')->onDelete('cascade'); // FK

            $table->longText('property_policies')->nullable();

            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade'); // FK to users table

            $table->timestamps();
            $table->softDeletes(); // Adds deleted_at column for soft deletes

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
