<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform')->index();
            $table->string('title')->nullable();
            $table->text('message');
            $table->string('audience')->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->string('media_url', 2048)->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('external_post_id')->nullable()->index();
            $table->string('external_permalink', 2048)->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['platform', 'status']);
            $table->index(['user_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
