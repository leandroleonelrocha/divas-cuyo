<?php

namespace Tests\Support;

use App\Models\Locality;
use App\Models\ModelPhoto;
use App\Models\ModelProfile;
use App\Models\ModelProfileSlug;
use App\Models\Province;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait PublicModelProfileFixtures
{
    protected function publicProfile(string $slug = 'mia'): ModelProfile
    {
        Storage::fake('model_photos');
        $province = Province::firstOrCreate(['slug' => 'mendoza'], ['name' => 'Mendoza']);
        $locality = Locality::firstOrCreate(['slug' => 'ciudad', 'province_id' => $province->id], ['name' => 'Ciudad de Mendoza']);
        $profile = User::factory()->create()->modelProfile;
        $profile->forceFill([
            'slug' => $slug, 'stage_name' => 'Mia', 'identity_status' => 'approved',
            'review_status' => 'approved', 'is_published' => true,
            'availability_status' => 'available', 'province_id' => $province->id,
            'locality_id' => $locality->id, 'approximate_location_text' => 'Zona centro',
        ])->save();
        (new ModelProfileSlug)->forceFill(['slug' => $slug, 'model_profile_id' => $profile->id])->save();
        $this->publicPhoto($profile, 0, true);

        return $profile->fresh();
    }

    protected function publicPhoto(ModelProfile $profile, int $position, bool $primary = false): ModelPhoto
    {
        $photo = $profile->photos()->create(['position' => $position, 'is_primary' => $primary]);
        $base = 'model-profiles/test/'.Str::uuid();
        $version = $photo->versions()->create([
            'version' => 1, 'original_path' => "$base/original.jpg", 'processed_path' => "$base/processed.webp",
            'public_path' => "$base/public.webp", 'thumbnail_path' => "$base/thumbnail.webp",
            'mime_type' => 'image/webp', 'file_size' => 100, 'width' => 80, 'height' => 80,
            'processed_width' => 80, 'processed_height' => 80, 'status' => 'approved',
        ]);
        // Synthetic, marked fixture only: production certification belongs to US3.
        $image = imagecreatetruecolor(80, 80);
        imagestring($image, 1, 4, 40, 'Divas Cuyo', imagecolorallocate($image, 255, 255, 255));
        ob_start();
        imagewebp($image);
        $contents = ob_get_clean();
        imagedestroy($image);
        Storage::disk('model_photos')->put($version->public_path, $contents);
        $version->forceFill(['public_watermarked_at' => now(), 'public_token' => (string) Str::uuid()])->save();
        $photo->update(['current_version_id' => $version->id]);

        return $photo->fresh();
    }
}
