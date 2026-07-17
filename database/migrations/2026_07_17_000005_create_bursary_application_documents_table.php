<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bursary_application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bursary_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bursary_document_requirement_id')->nullable();
            $table->string('document_key');
            $table->string('original_name');
            $table->string('storage_disk')->default('local');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->foreign('bursary_document_requirement_id', 'bad_requirement_id_foreign')
                ->references('id')
                ->on('bursary_document_requirements')
                ->nullOnDelete();
            $table->index(['bursary_application_id', 'document_key'], 'bad_application_document_key_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bursary_application_documents');
    }
};
