<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name')->nullable()->change();
            $table->string('whatsapp')->nullable()->change();
            $table->string('location')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Complete or remove pending registrations before reverting this schema.
        if (DB::table('users')->whereNull('name')
            ->orWhereNull('whatsapp')->orWhereNull('location')->exists()) {
            throw new RuntimeException('Hay cuentas pendientes de completar. No se puede restaurar NOT NULL.');
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('name')->nullable(false)->change();
            $table->string('whatsapp')->nullable(false)->change();
            $table->string('location')->nullable(false)->change();
        });
    }
};
