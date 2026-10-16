<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\ImportModelPhotos;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;
use ZipArchive;

class ImportModelPhotosPageTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_page_analyzes_and_imports_zip(): void
    {
        $profile = $this->publicProfile();
        $admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
        $this->actingAs($admin);

        $zipPath = $this->makeZip([$profile->slug => 2]);
        $file = UploadedFile::fake()->create('fotos.zip', (int) ceil(filesize($zipPath) / 1024), 'application/zip');
        file_put_contents($file->getRealPath(), file_get_contents($zipPath));

        $component = Livewire::test(ImportModelPhotos::class)
            ->set('file', $file)
            ->call('analyze')
            ->assertHasNoErrors();

        $preview = $component->get('preview');
        $this->assertCount(1, $preview);
        $this->assertSame($profile->id, $preview[0]['profileId']);
        $this->assertSame(2, $preview[0]['files']);

        $component->call('import', false)->assertHasNoErrors();
        $this->assertSame(3, $profile->photos()->count());

        $this->assertSame(
            ['imported' => 2, 'omitted' => 0, 'profiles' => 1, 'errors' => []],
            $component->get('result'),
        );
    }

    public function test_zip_without_folders_uses_zip_name_to_match_profile(): void
    {
        $profile = $this->publicProfile();
        $admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
        $this->actingAs($admin);

        $zipPath = $this->makeFlatZip(1);
        $file = UploadedFile::fake()->create($profile->slug.'.zip', (int) ceil(filesize($zipPath) / 1024), 'application/zip');
        file_put_contents($file->getRealPath(), file_get_contents($zipPath));

        $component = Livewire::test(ImportModelPhotos::class)
            ->set('file', $file)
            ->call('analyze')
            ->assertHasNoErrors();

        $preview = $component->get('preview');
        $this->assertCount(1, $preview);
        $this->assertSame($profile->id, $preview[0]['profileId']);
        $this->assertSame(1, $preview[0]['files']);

        $component->call('import', false)->assertHasNoErrors();
        $this->assertSame(2, $profile->photos()->count());
    }

    private function makeFlatZip(int $count): string
    {
        $base = sys_get_temp_dir().'/zipflat-'.uniqid();
        mkdir($base, 0755, true);
        for ($i = 1; $i <= $count; $i++) {
            $img = imagecreatetruecolor(800, 800);
            imagejpeg($img, $base.'/0'.$i.'.jpg');
            imagedestroy($img);
        }
        $zipPath = $base.'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        foreach (glob($base.'/*.jpg') ?: [] as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        return $zipPath;
    }

    private function makeZip(array $folders): string
    {
        $base = sys_get_temp_dir().'/ziptest-'.uniqid();
        mkdir($base, 0755, true);
        foreach ($folders as $folder => $count) {
            mkdir($base.'/'.$folder, 0755, true);
            for ($i = 1; $i <= $count; $i++) {
                $img = imagecreatetruecolor(800, 800);
                imagejpeg($img, $base.'/'.$folder.'/0'.$i.'.jpg');
                imagedestroy($img);
            }
        }
        $zipPath = $base.'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        foreach (glob($base.'/*/*') ?: [] as $file) {
            $zip->addFile($file, basename(dirname($file)).'/'.basename($file));
        }
        $zip->close();

        return $zipPath;
    }
}
