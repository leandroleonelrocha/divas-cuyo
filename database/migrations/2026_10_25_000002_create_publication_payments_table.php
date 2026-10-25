<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('model_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registered_by')->constrained('users')->restrictOnDelete();
            $table->date('paid_at');
            $table->decimal('amount', 12, 2);
            $table->unsignedInteger('period_days');
            $table->date('starts_at');
            $table->date('expires_at');
            $table->text('internal_note')->nullable();
            $table->timestamps();
            $table->index(['model_profile_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_payments');
    }
};
