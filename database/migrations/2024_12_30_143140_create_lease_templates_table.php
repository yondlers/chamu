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
        Schema::create('lease_templates', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);


            $table->string('name');
            $table->string('slug');


            $table->longText('template_html')->nullable();

            //Incase tenant or landlord upload signed contract
            //Keep in this order,
            $table->string('file_prefix')->unique()->nullable();
            $table->string('lease_template_file')->unique()->nullable();

            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade'); // FK to teams table

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_templates');
    }
};
