<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_profile_publication_type_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('model_profile_id');
            $table->foreignId('from_publication_type_id')->nullable();
            $table->foreignId('to_publication_type_id');
            $table->foreignId('changed_by_user_id')->nullable();
            $table->string('source', 20);
            $table->text('reason')->nullable();
            $table->timestamp('changed_at')->index('mph_changed_at_idx');
            $table->timestamps();

            $table->foreign('model_profile_id', 'mph_profile_fk')->references('id')->on('model_profiles')->cascadeOnDelete();
            $table->foreign('from_publication_type_id', 'mph_from_type_fk')->references('id')->on('publication_types')->nullOnDelete();
            $table->foreign('to_publication_type_id', 'mph_to_type_fk')->references('id')->on('publication_types')->restrictOnDelete();
            $table->foreign('changed_by_user_id', 'mph_changed_by_fk')->references('id')->on('users')->nullOnDelete();

            $table->index(['model_profile_id', 'changed_at'], 'mph_profile_changed_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_profile_publication_type_history');
    }
};
