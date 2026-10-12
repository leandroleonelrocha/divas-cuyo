<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_profiles', function (Blueprint $table): void {
            $table->dropColumn('rate');
            $table->string('rate_visibility', 20)->default('consult')->after('depilation');
            $table->decimal('rate_30min', 10, 2)->nullable()->after('rate_visibility');
            $table->string('rate_30min_currency', 3)->nullable()->after('rate_30min');
            $table->decimal('rate_60min', 10, 2)->nullable()->after('rate_30min_currency');
            $table->string('rate_60min_currency', 3)->nullable()->after('rate_60min');
        });
    }

    public function down(): void
    {
        Schema::table('model_profiles', function (Blueprint $table): void {
            $table->dropColumn(['rate_visibility', 'rate_30min', 'rate_30min_currency', 'rate_60min', 'rate_60min_currency']);
            $table->string('rate', 100)->nullable()->after('depilation');
        });
    }
};
