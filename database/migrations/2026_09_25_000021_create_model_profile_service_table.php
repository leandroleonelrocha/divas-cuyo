<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_profile_service', function (Blueprint $table): void {
            $table->foreignId('model_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['model_profile_id', 'service_id']);
            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_profile_service');
    }
};
