<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_photo_versions', function (Blueprint $table): void {
            // Existing files are not automatically certified. Preparation belongs to US3.
            $table->timestamp('public_watermarked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('model_photo_versions', function (Blueprint $table): void {
            $table->dropColumn('public_watermarked_at');
        });
    }
};
