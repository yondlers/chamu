<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\LookUp;
use App\Models\MaritalStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('marital_statuses', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);
            $table->string('name');
            $table->timestamps();
        });
        
        foreach (LookUp::MARITAL_STATUS as $martial_status) {
            MaritalStatus::updateOrCreate(
                ['id' => $martial_status['id']],
                $martial_status
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marital_statuses');
    }
};
