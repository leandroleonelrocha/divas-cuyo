<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_profiles', function (Blueprint $table): void {
            $table->foreignId('province_id')->nullable()->after('availability_status')->constrained()->nullOnDelete();
            $table->foreignId('locality_id')->nullable()->after('province_id')->constrained()->nullOnDelete();
            $table->string('approximate_location_text')->nullable()->after('locality_id');
            $table->decimal('approximate_latitude', 10, 7)->nullable()->after('approximate_location_text');
            $table->decimal('approximate_longitude', 10, 7)->nullable()->after('approximate_latitude');

            $table->index(['province_id', 'locality_id']);
        });
    }

    public function down(): void
    {
        Schema::table('model_profiles', function (Blueprint $table): void {
            $table->dropForeign(['locality_id']);
            $table->dropForeign(['province_id']);
            $table->dropIndex(['province_id', 'locality_id']);
            $table->dropColumn([
                'province_id',
                'locality_id',
                'approximate_location_text',
                'approximate_latitude',
                'approximate_longitude',
            ]);
        });
    }
};
