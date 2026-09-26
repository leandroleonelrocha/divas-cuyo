<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('model_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('storage_path', 512);
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['model_profile_id', 'type']);
            $table->index(['model_profile_id', 'status']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_documents');
    }
};
