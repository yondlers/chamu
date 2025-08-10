<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\City;
use App\Helpers\LookUp;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();

            $table->boolean('active')->default(true);
            $table->string('name');
            $table->foreignId('province_id')->nullable()->constrained('provinces')->onDelete('cascade');

            $table->timestamps();
        });

        foreach (LookUp::CITIES_OPTIONS as $city) {
            City::updateOrCreate(
                ['id' => $city['id']],
                $city
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
