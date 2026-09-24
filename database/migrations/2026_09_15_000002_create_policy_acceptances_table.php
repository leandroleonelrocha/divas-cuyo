<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_acceptances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('policy', 32);
            $table->string('version', 32);
            $table->timestamp('accepted_at');
            $table->timestamps();

            $table->unique(['user_id', 'policy']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_acceptances');
    }
};
