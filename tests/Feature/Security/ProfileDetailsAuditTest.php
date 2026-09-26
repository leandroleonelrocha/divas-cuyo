<?php

namespace Tests\Feature\Security;

use App\Enums\ModelProfileAvailabilityStatus;
use App\Enums\ModelProfileBioStatus;
use App\Enums\ModelProfilePhysicalRevisionStatus;
use App\Enums\PublicationTypeServiceType;
use App\Models\ModelProfileBio;
use App\Models\ModelProfilePublicationTypeHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDetailsAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_enums_keep_the_persisted_values_explicit(): void
    {
        $this->assertSame('available', ModelProfileAvailabilityStatus::Available->value);
        $this->assertSame('unavailable', ModelProfileAvailabilityStatus::Unavailable->value);
        $this->assertSame(['pending', 'approved', 'rejected'], array_column(ModelProfileBioStatus::cases(), 'value'));
        $this->assertSame(['pending', 'approved', 'rejected'], array_column(ModelProfilePhysicalRevisionStatus::cases(), 'value'));
        $this->assertSame(['virtual', 'in_person'], array_column(PublicationTypeServiceType::cases(), 'value'));
    }

    public function test_relationship_foreign_keys_are_not_mass_assignable_from_model_payloads(): void
    {
        $bio = new ModelProfileBio(['model_profile_id' => 999, 'content' => 'Texto válido.']);
        $history = new ModelProfilePublicationTypeHistory(['model_profile_id' => 999, 'source' => 'model']);

        $this->assertNull($bio->model_profile_id);
        $this->assertNull($history->model_profile_id);
    }
}
