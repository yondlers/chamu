<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\LookUp;
use App\Models\Ethnicity;



return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ethnicities', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);

            $table->string('name');

            $table->timestamps();
        });

        foreach (LookUp::ETHNICITY_TYPES as $ethnicity) {
            Ethnicity::updateOrCreate(
                ['id' => $ethnicity['id']],
                $ethnicity
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ethnicities');
    }
};
