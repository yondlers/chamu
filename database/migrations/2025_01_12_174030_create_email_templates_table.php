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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();

            $table->boolean('active')->default(true);

            $table->string('name');
            $table->string('subject');

            $table->longText('template_html')->nullable();

            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade'); // FK to teams table

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
