<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Province;
use App\Helpers\LookUp;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();

            $table->boolean('active')->default(true);
            $table->string('name');
            $table->foreignId('country_id')->nullable()->constrained('countries')->onDelete('cascade');

            $table->timestamps();
        });

        foreach (LookUp::PROVINCES_OPTIONS as $province) {
            Province::updateOrCreate(
                ['id' => $province['id']],
                $province
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
