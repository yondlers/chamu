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
        Schema::create('signature_authorizations', function (Blueprint $table) {
            $table->id();

            $table->boolean('active')->default(true);
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            $table->string('name');
            $table->string('position')->nullable();
            $table->string('id_number');
            $table->string('contact_number');
            $table->string('alternative_number')->nullable();
            $table->string('contact_email');
            $table->longText('signature_image');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_authorizations');
    }
};
