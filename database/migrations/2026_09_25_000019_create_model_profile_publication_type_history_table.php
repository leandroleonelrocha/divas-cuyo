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
            $table->foreignId('model_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_publication_type_id')->nullable()->constrained('publication_types')->nullOnDelete();
            $table->foreignId('to_publication_type_id')->constrained('publication_types')->restrictOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 20);
            $table->text('reason')->nullable();
            $table->timestamp('changed_at')->index();
            $table->timestamps();

            $table->index(['model_profile_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_profile_publication_type_history');
    }
};
