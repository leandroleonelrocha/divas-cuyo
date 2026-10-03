<?php

namespace Tests\Feature\PublicModels;

use App\Models\ModelDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicModelPrivacyTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_public_content_has_no_private_or_administrative_data_and_uses_selective_queries(): void
    {
        $profile = $this->publicProfile();
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'REVISOR_PRIVADO']);
        $profile->privateDetails()->create(['real_first_name' => 'NOMBRE_PRIVADO', 'real_last_name' => 'APELLIDO_PRIVADO', 'birth_date' => '1971-02-03', 'private_phone' => 'TELEFONO_PRIVADO', 'real_height_cm' => 199, 'real_weight_kg' => 99, 'real_measurements' => 'MEDIDAS_PRIVADAS']);
        $profile->forceFill(['name' => 'NOMBRE_LEGACY', 'whatsapp' => 'WHATSAPP_PRIVADO', 'location' => 'DOMICILIO_EXACTO', 'height_cm' => 168, 'public_age' => 37, 'show_age' => false, 'reviewed_by' => $admin->id, 'identity_rejection_reason' => 'MOTIVO_PERFIL', 'approximate_latitude' => -32.1234567, 'approximate_longitude' => -68.1234567])->save();
        foreach (['dni_front', 'selfie'] as $type) {
            (new ModelDocument)->forceFill(['model_profile_id' => $profile->id, 'type' => $type, 'storage_path' => 'identity/SECRET_'.$type, 'original_name' => 'SECRET_DOCUMENT_'.$type, 'mime_type' => 'image/jpeg', 'file_size' => 100, 'status' => 'rejected', 'rejection_reason' => 'MOTIVO_DOCUMENTO', 'reviewed_by' => $admin->id])->save();
        }
        $bio = $profile->bios()->create(['content' => 'BIO_PUBLICA_SEGURA', 'status' => 'approved', 'reviewed_by' => $admin->id, 'rejection_reason' => 'MOTIVO_BIO']);
        $profile->update(['current_bio_id' => $bio->id]);
        $profile->physicalRevisions()->create(['height_cm' => 191, 'weight_kg' => 88, 'measurements' => 'MEDIDAS_PROPUESTAS', 'nationality' => 'NACIONALIDAD_PROPUESTA', 'eye_color' => 'REVISION_PRIVADA', 'status' => 'pending', 'submitted_by' => $profile->user_id]);
        $version = $profile->photos()->first()->currentVersion;
        $before = $profile->fresh()->getAttributes();
        Model::preventLazyLoading();
        DB::enableQueryLog();
        try {
            $response = $this->get('/modelos/mia')->assertOk()->assertSee('BIO_PUBLICA_SEGURA')->assertSee('1,68 m');
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
            Model::preventLazyLoading(false);
        }
        $data = $response->viewData('publicProfile');
        $this->assertSame($before, $profile->fresh()->getAttributes());
        foreach (['NOMBRE_PRIVADO', 'APELLIDO_PRIVADO', '1971-02-03', 'TELEFONO_PRIVADO', 'MEDIDAS_PRIVADAS', 'NOMBRE_LEGACY', 'WHATSAPP_PRIVADO', 'DOMICILIO_EXACTO', 'REVISOR_PRIVADO', 'MOTIVO_PERFIL', 'MOTIVO_DOCUMENTO', 'MOTIVO_BIO', 'SECRET_dni_front', 'SECRET_selfie', 'SECRET_DOCUMENT', 'REVISION_PRIVADA', $profile->user->email, '-32.1234567', '-68.1234567', $version->original_path, $version->processed_path, $version->thumbnail_path, $version->public_path] as $secret) {
            $response->assertDontSee($secret, false);
            $this->assertStringNotContainsString($secret, serialize($data));
        }
        foreach (['user_id', 'model_profile_id', 'current_bio_id', 'reviewed_by', 'reviewed_at', 'storage_path', 'rejection_reason', 'birth_date', 'show_age', 'public_age'] as $key) {
            $response->assertDontSee($key, false);
            $this->assertStringNotContainsString($key, serialize($data));
        }
        foreach ((array) $data as $value) {
            $this->assertTrue(is_scalar($value) || is_array($value) || $value === null);
        }
        foreach ($queries as $query) {
            foreach (['model_profile_private_details', 'model_documents', 'model_profile_physical_revisions', 'publication_type_history', 'birth_date', 'approximate_latitude', 'approximate_longitude'] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $query['query']);
            }
        }
        $profile->update(['is_published' => false]);
        $this->get('/modelos/mia')->assertNotFound()->assertDontSee('BIO_PUBLICA_SEGURA')->assertDontSee('1,68 m');
    }
}
