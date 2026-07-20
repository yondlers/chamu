<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_post_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->string('platform')->index();
            $table->string('response_type')->index();
            $table->string('external_response_id')->nullable()->index();
            $table->string('author_name')->nullable();
            $table->string('author_handle')->nullable();
            $table->text('body')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['social_post_id', 'response_type']);
            $table->index(['platform', 'response_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_post_responses');
    }
};
