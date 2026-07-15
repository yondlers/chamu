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
        Schema::create('suburbs', function (Blueprint $table) {
            $table->id();

            $table->boolean('active')->default(true);
            $table->string('name');
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suburbs');
    }
};
