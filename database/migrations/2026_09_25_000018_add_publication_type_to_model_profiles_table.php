<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_profiles', function (Blueprint $table): void {
            $table->foreignId('publication_type_id')
                ->nullable()
                ->after('availability_status')
                ->constrained('publication_types')
                ->nullOnDelete();

            $table->index('publication_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('model_profiles', function (Blueprint $table): void {
            $table->dropForeign(['publication_type_id']);
            $table->dropIndex(['publication_type_id']);
            $table->dropColumn('publication_type_id');
        });
    }
};
