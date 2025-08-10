<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\LookUp;
use App\Models\Language;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);

            $table->string('name');
            // $table->string('description')->nullable();
            $table->timestamps();
        });

        foreach (LookUp::LANGAUGE_TYPES as $language) {
            Language::updateOrCreate(
                ['id' => $language['id']],
                $language
            );
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
