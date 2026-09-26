<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_profile_bios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('model_profile_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->string('status', 20)->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['model_profile_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_profile_bios');
    }
};
