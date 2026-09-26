<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_profiles', function (Blueprint $table): void {
            $table->foreignId('current_bio_id')
                ->nullable()
                ->after('publication_type_id')
                ->constrained('model_profile_bios')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('model_profiles', function (Blueprint $table): void {
            $table->dropForeign(['current_bio_id']);
            $table->dropColumn('current_bio_id');
        });
    }
};
