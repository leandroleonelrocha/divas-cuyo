<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_videos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('model_profile_id')->constrained()->cascadeOnDelete();
            $table->uuid('public_token')->unique();
            $table->string('original_path')->nullable();
            $table->string('original_name');
            $table->unsignedBigInteger('original_size');
            $table->string('provider');
            $table->string('resource_id')->nullable();
            $table->json('hosting_history')->nullable();
            $table->string('processing_status')->default('queued');
            $table->string('moderation_status')->default('pending');
            $table->unsignedInteger('position')->default(0);
            $table->decimal('duration', 8, 3)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamp('watermarked_at')->nullable();
            $table->text('error_message')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['model_profile_id', 'processing_status', 'moderation_status'], 'model_videos_visibility_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_videos');
    }
};
