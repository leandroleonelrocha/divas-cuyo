<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_profiles', function (Blueprint $table): void {
            $table->string('schedule_availability', 20)->nullable()->after('availability_status');
            $table->string('gender', 20)->nullable()->after('schedule_availability');
            $table->string('depilation', 20)->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('model_profiles', function (Blueprint $table): void {
            $table->dropColumn(['schedule_availability', 'gender', 'depilation']);
        });
    }
};
