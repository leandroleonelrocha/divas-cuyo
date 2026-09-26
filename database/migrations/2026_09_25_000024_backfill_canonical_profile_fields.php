<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('model_profiles')
            ->whereNull('stage_name')
            ->whereNotNull('name')
            ->update(['stage_name' => new Expression('name')]);

        DB::table('model_profiles')
            ->whereNull('approximate_location_text')
            ->whereNotNull('location')
            ->update(['approximate_location_text' => new Expression('location')]);
    }

    public function down(): void
    {
        DB::table('model_profiles')
            ->whereColumn('stage_name', 'name')
            ->update(['stage_name' => null]);

        DB::table('model_profiles')
            ->whereColumn('approximate_location_text', 'location')
            ->update(['approximate_location_text' => null]);
    }
};
