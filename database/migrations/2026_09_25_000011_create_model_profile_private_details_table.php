<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_profile_private_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('model_profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('real_first_name');
            $table->string('real_last_name');
            $table->date('birth_date');
            $table->unsignedSmallInteger('real_height_cm')->nullable();
            $table->decimal('real_weight_kg', 5, 2)->nullable();
            $table->string('real_measurements')->nullable();
            $table->string('nationality')->nullable();
            $table->string('private_phone', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_profile_private_details');
    }
};
