<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_photos', function (Blueprint $table): void {
            $table->foreign('current_version_id')
                ->references('id')
                ->on('model_photo_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('model_photos', function (Blueprint $table): void {
            $table->dropForeign(['current_version_id']);
        });
    }
};
