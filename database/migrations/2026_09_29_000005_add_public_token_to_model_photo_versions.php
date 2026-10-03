<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_photo_versions', function (Blueprint $table): void {
            $table->uuid('public_token')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('model_photo_versions', function (Blueprint $table): void {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};
