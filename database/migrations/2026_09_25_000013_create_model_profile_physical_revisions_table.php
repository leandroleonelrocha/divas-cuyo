<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_profile_physical_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('model_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('height_cm');
            $table->decimal('weight_kg', 5, 2);
            $table->string('measurements');
            $table->string('eye_color');
            $table->string('hair_color')->nullable();
            $table->string('skin_color')->nullable();
            $table->string('body_type')->nullable();
            $table->string('nationality');
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['model_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_profile_physical_revisions');
    }
};
