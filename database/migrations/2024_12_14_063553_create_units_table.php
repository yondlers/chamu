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
        Schema::create('units', function (Blueprint $table) {

            $table->id();

            $table->boolean('active')->default(true);

            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade'); // FK to properties table
            $table->foreignId('unit_type_id')->nullable()->constrained('unit_types')->onDelete('cascade'); // FK to unit_types table
            $table->foreignId('asset_information_id')->nullable()->constrained('asset_informations')->onDelete('cascade'); // FK


            $table->decimal('monthly_rent', 10, 2); // Rent for the unit

            $table->string('unit_number'); // Identifier for the unit (e.g., "A101")

            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade'); // FK to teams table

            $table->timestamps();
            $table->softDeletes(); // Adds deleted_at column for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
