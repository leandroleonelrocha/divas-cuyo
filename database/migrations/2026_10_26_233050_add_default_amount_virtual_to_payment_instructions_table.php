<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_instructions', function (Blueprint $table): void {
            $table->decimal('default_amount_virtual', 12, 2)->nullable()->after('default_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_instructions', function (Blueprint $table): void {
            $table->dropColumn('default_amount_virtual');
        });
    }
};
