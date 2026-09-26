<?php

namespace Tests\Feature\Profile;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfileSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_us1_profile_schema_exists_without_removing_legacy_columns(): void
    {
        $this->assertTrue(Schema::hasTable('model_profile_private_details'));
        $this->assertTrue(Schema::hasTable('model_profile_physical_revisions'));
        $this->assertTrue(Schema::hasColumns('model_profile_private_details', [
            'model_profile_id',
            'real_first_name',
            'real_last_name',
            'birth_date',
            'private_phone',
        ]));
        $this->assertTrue(Schema::hasColumns('model_profiles', [
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
        ]));
        $this->assertTrue(Schema::hasColumns('model_profiles', ['name', 'whatsapp', 'location']));
    }
}
