<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->orderBy('id')
            ->eachById(function (object $user): void {
                $published = (bool) $user->is_published;

                DB::table('model_profiles')->insert([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'whatsapp' => $user->whatsapp,
                    'location' => $user->location,
                    'review_status' => $published ? 'approved' : 'pending',
                    'is_published' => $published,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        DB::table('model_profiles')->delete();
    }
};
