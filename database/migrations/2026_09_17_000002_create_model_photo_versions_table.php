<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_photo_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('model_photo_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->unsignedBigInteger('supersedes_version_id')->nullable();
            $table->string('original_path', 512);
            $table->string('processed_path', 512);
            $table->string('public_path', 512);
            $table->string('thumbnail_path', 512);
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('processed_width');
            $table->unsignedInteger('processed_height');
            $table->string('status', 20)->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['model_photo_id', 'version']);
            $table->index(['model_photo_id', 'status']);
            $table->foreign('supersedes_version_id')
                ->references('id')
                ->on('model_photo_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_photo_versions');
    }
};
