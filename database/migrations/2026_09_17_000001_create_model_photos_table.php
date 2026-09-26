<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('model_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['model_profile_id', 'position']);
            $table->index(['model_profile_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_photos');
    }
};
