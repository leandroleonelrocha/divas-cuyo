<?php

namespace Tests\Feature\Identity;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IdentityDocumentMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_schema_has_expected_columns_and_constraints(): void
    {
        $this->assertTrue(Schema::hasColumns('model_profiles', [
            'identity_status',
            'identity_reviewed_at',
            'identity_reviewed_by',
            'identity_rejection_reason',
        ]));

        $this->assertTrue(Schema::hasColumns('model_documents', [
            'model_profile_id',
            'type',
            'storage_path',
            'original_name',
            'mime_type',
            'file_size',
            'status',
            'rejection_reason',
            'reviewed_at',
            'reviewed_by',
        ]));

        $indexes = collect(Schema::getIndexes('model_documents'))->pluck('name');

        $this->assertTrue($indexes->contains('model_documents_model_profile_id_type_unique'));
        $this->assertTrue($indexes->contains('model_documents_model_profile_id_status_index'));
        $this->assertTrue($indexes->contains('model_documents_type_index'));
    }
}
