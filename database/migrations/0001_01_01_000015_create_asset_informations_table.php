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
        Schema::create('asset_informations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');


            //Estate or multiple home living (i.e. Apartments, Flats, Townhouse)
            $table->boolean('is_estate')->default(false);
            // $table->decimcal('number_of_units', 8, 2)->nullable();
            $table->decimal('number_of_units')->default(0)->nullable();
            $table->string('name')->nullable();
            $table->text('estate_description')->nullable();
            $table->boolean('gated_community')->default(false)->nullable();
            $table->boolean('has_clubhouse')->default(false)->nullable();
            $table->boolean('has_gym')->default(false)->nullable();
            $table->boolean('has_tennis_court')->default(false)->nullable();
            $table->boolean('has_golf_course')->default(false)->nullable();
            $table->boolean('has_communal_pool')->default(false)->nullable();
            $table->boolean('has_communal_garden')->default(false)->nullable();
            $table->boolean('has_communal_park')->default(false)->nullable();
            $table->boolean('has_communal_braai')->default(false)->nullable();
            $table->boolean('has_communal_area')->default(false)->nullable();
            $table->boolean('has_parking')->default(false)->nullable();
            $table->text('complex_description')->nullable();
            $table->integer('number_of_buildings_in_complex')->default(0)->nullable();

            // Property specific fields
            $table->boolean('is_property')->default(true);
            $table->decimal('number_of_bathrooms')->default(0)->nullable();
            $table->decimal('number_of_garages')->default(0)->nullable();
            $table->decimal('number_of_bedrooms')->default(0)->nullable();
            $table->decimal('number_of_kitchens')->default(0)->nullable();
            $table->decimal('number_of_parking')->default(0)->nullable();
            $table->string('out_buildings')->nullable();
            $table->integer('year_built')->nullable();
            $table->integer('number_of_floors')->default(1)->nullable();
            $table->boolean('has_fireplace')->default(false)->nullable();
            $table->boolean('has_study')->default(false)->nullable();
            $table->boolean('has_laundry_room')->default(false)->nullable();
            $table->boolean('has_storage_room')->default(false)->nullable();

            // Room specific fields (for communal living houses)
            $table->boolean('is_room')->default(false);
            $table->decimal('room_size_sqm', 10, 2)->nullable();
            $table->string('room_features')->nullable(); // e.g., "ensuite", "built-in desk"
            $table->integer('number_of_beds_in_room')->default(1)->nullable();
            $table->boolean('has_private_bathroom')->default(false)->nullable();
            $table->boolean('has_private_kitchen')->default(false)->nullable();

            // Room sharing specific fields
            $table->boolean('is_room_sharing')->default(false);
            $table->integer('number_of_occupants_in_room')->default(1)->nullable();
            $table->enum('room_sharing_gender_preference', ['male', 'female', 'mixed'])->nullable();
            $table->text('room_sharing_rules')->nullable();

            // General features and amenities
            $table->boolean('is_furnished')->nullable();
            $table->boolean('is_pet_friendly')->default(false)->nullable();
            $table->boolean('has_disability_access')->default(false)->nullable();
            $table->boolean('has_pool')->default(false)->nullable();
            $table->boolean('has_garden')->default(false)->nullable();
            $table->boolean('has_balcony')->default(false)->nullable();
            $table->string('security_features')->nullable();
            $table->boolean('has_air_conditioning')->default(false)->nullable();
            $table->boolean('has_heating')->default(false)->nullable();
            $table->boolean('has_built_in_cupboards')->default(false)->nullable();
            $table->boolean('has_braai_area')->default(false)->nullable(); // BBQ area

            //Security
            $table->boolean('has_biometric')->default(false)->nullable();
            $table->boolean('has_intercom_system')->default(false)->nullable();
            $table->boolean('has_electic_fence')->default(false)->nullable();
            $table->boolean('has_security')->default(false)->nullable();
            $table->boolean('has_cctv')->default(false)->nullable();
            $table->boolean('has_alarm_system')->default(false)->nullable(); // Added another common security feature
            $table->boolean('has_armed_response')->default(false)->nullable(); // Added another common security feature

            //Extra
            $table->decimal('km_from_hospital', 8, 2)->nullable();
            $table->decimal('km_from_school', 8, 2)->nullable();
            $table->decimal('km_from_police', 8, 2)->nullable();
            $table->decimal('km_from_mall', 8, 2)->nullable();

            $table->decimal('floor_size', 10, 2)->nullable(); // Size in square meters
            $table->decimal('erf_size', 10, 2);   // Size in square meters


            $table->boolean('has_wifi')->nullable();
            $table->string('electricity_meter')->nullable();
            $table->string('water_meter')->nullable();
            $table->enum('utility_type', ['prepaid', 'postpaid'])->nullable();
            $table->boolean('fiber_ready')->default(false)->nullable();
            $table->boolean('gas')->default(false)->nullable();
            $table->string('backup_power')->nullable();
            $table->boolean('solar_panels')->default(false);
            $table->boolean('borehole')->default(false); // Water borehole


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_informations');
    }
};
