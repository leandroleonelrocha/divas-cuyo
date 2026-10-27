<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('model_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->text('detail')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['model_profile_id', 'read_at']);
            $table->index(['model_profile_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_notifications');
    }
};
