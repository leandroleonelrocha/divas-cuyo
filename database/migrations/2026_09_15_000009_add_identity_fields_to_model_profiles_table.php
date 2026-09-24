<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_profiles', function (Blueprint $table): void {
            $table->string('identity_status', 20)->default('incomplete')->index()->after('location');
            $table->timestamp('identity_reviewed_at')->nullable()->after('identity_status');
            $table->foreignId('identity_reviewed_by')
                ->nullable()
                ->after('identity_reviewed_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('identity_rejection_reason')->nullable()->after('identity_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('model_profiles', function (Blueprint $table): void {
            $table->dropForeign(['identity_reviewed_by']);
            $table->dropIndex(['identity_status']);
            $table->dropColumn([
                'identity_status',
                'identity_reviewed_at',
                'identity_reviewed_by',
                'identity_rejection_reason',
            ]);
        });
    }
};
