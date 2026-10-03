<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $versions = new class extends Model
        {
            protected $table = 'model_photo_versions';

            public $timestamps = false;
        };

        $versions->newQuery()->select('id')->whereNull('public_token')
            ->chunkById(100, function ($batch) use ($versions): void {
                foreach ($batch as $version) {
                    $versions->newQuery()->whereKey($version->id)->whereNull('public_token')
                        ->update(['public_token' => (string) Str::uuid()]);
                }
            });
    }

    public function down(): void
    {
        // Preserve issued URLs until the separate schema rollback removes the column.
    }
};
