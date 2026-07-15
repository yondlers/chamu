<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\LookUp;
use App\Models\MaintenanceType;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_types', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);

            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        foreach (LookUp::MAINTENANCE_TYPES as $maintenance_type) {
            MaintenanceType::updateOrCreate(
                ['id' => $maintenance_type['id']],
                $maintenance_type
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_types');
    }
};
