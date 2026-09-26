<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_profiles', function (Blueprint $table): void {
            $table->string('stage_name')->nullable()->after('name');
            $table->unsignedTinyInteger('public_age')->nullable()->after('stage_name');
            $table->boolean('show_age')->default(false)->after('public_age');
            $table->unsignedSmallInteger('height_cm')->nullable()->after('show_age');
            $table->decimal('weight_kg', 5, 2)->nullable()->after('height_cm');
            $table->string('measurements')->nullable()->after('weight_kg');
            $table->string('eye_color')->nullable()->after('measurements');
            $table->string('hair_color')->nullable()->after('eye_color');
            $table->string('skin_color')->nullable()->after('hair_color');
            $table->string('body_type')->nullable()->after('skin_color');
            $table->string('nationality')->nullable()->after('body_type');
        });
    }

    public function down(): void
    {
        Schema::table('model_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'stage_name',
                'public_age',
                'show_age',
                'height_cm',
                'weight_kg',
                'measurements',
                'eye_color',
                'hair_color',
                'skin_color',
                'body_type',
                'nationality',
            ]);
        });
    }
};
