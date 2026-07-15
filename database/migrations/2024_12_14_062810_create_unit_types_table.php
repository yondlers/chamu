<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\LookUp;
use App\Models\UnitType;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unit_types', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);

            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        foreach (LookUp::UNIT_TYPES as $unit_type) {
            UnitType::updateOrCreate(
                ['id' => $unit_type['id']],
                $unit_type
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_types');
    }
};
