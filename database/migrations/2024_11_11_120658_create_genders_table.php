<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\LookUp;
use App\Models\Gender;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('genders', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);

            $table->string('name');

            $table->timestamps();
        });

        foreach (LookUp::GENDERS_OPTIONS as $gender) {
            Gender::updateOrCreate(
                ['id' => $gender['id']],
                $gender
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('genders');
    }
};
