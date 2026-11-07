<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_stat_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_profile_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('visits')->default(0);
            $table->unsignedInteger('whatsapp_clicks')->default(0);
            $table->unique(['model_profile_id', 'day']);
        });
        Schema::create('profile_stat_visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_profile_id')->constrained()->cascadeOnDelete();
            $table->char('visitor_hash', 64);
            $table->string('event', 20);
            $table->timestamp('counted_at')->index();
            $table->unique(['model_profile_id', 'visitor_hash', 'event'], 'profile_stat_visitor_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_stat_visitors');
        Schema::dropIfExists('profile_stat_days');
    }
};
