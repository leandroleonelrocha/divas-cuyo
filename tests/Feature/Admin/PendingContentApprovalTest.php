<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ModelProfiles\Pages\ViewModelProfile;
use App\Models\User;
use App\Services\AccountEnrollmentState;
use App\Services\PendingContentApproval;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PendingContentApprovalTest extends TestCase
{
    private function fixture(): array
    {
        Storage::fake('model_photos');
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->withCompletedRegistration()->create();
        $owner->forceFill(['profile_completion_status' => AccountEnrollmentState::ACTIVE])->save();
        $profile = $owner->modelProfile;
        $profile->forceFill(['identity_status' => 'approved', 'review_status' => 'approved'])->save();

        return [$admin, $owner, $profile];
    }

    public function test_modal_approves_selected_content_and_keeps_unselected_pending(): void
    {
        [$admin, $owner, $profile] = $this->fixture();
        $bio = $profile->bios()->create(['content' => 'Biografía para revisar', 'status' => 'pending']);
        $physical = $profile->physicalRevisions()->create(['height_cm' => 170, 'weight_kg' => 60, 'measurements' => '90-60-90', 'eye_color' => 'Marrón', 'nationality' => 'Argentina', 'status' => 'pending', 'submitted_by' => $owner->id]);
        $this->actingAs($admin);
        $page = Livewire::test(ViewModelProfile::class, ['record' => $profile->id])->set('activeRelationManager', '0')
            ->assertSee('Aprobar pendientes')->mountAction('approvePending')->assertActionMounted('approvePending');
        $items = $page->get('pendingApprovalItems');
        $key = collect($items)->firstWhere('type', 'bio')['key'];
        $page->setActionData(['selected' => [$key]])->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame('approved', $bio->fresh()->status);
        $this->assertSame($admin->id, $bio->fresh()->reviewed_by);
        $this->assertSame('pending', $physical->fresh()->status);
        $this->assertFalse($profile->fresh()->is_published);
        $page->assertSee('Resultado de la aprobación');
    }

    public function test_changed_snapshot_and_already_resolved_items_are_not_approved(): void
    {
        [$admin, , $profile] = $this->fixture();
        $bio = $profile->bios()->create(['content' => 'Versión inicial', 'status' => 'pending']);
        $service = app(PendingContentApproval::class);
        $key = $service->items($profile, $admin)[0]['key'];
        $bio->update(['content' => 'Versión distinta']);
        $result = $service->approve($profile, $admin, [$key]);
        $this->assertFalse($result[0]['approved']);
        $this->assertSame('pending', $bio->fresh()->status);
        $key = $service->items($profile, $admin)[0]['key'];
        $this->assertTrue($service->approve($profile, $admin, [$key])[0]['approved']);
        $this->assertFalse($service->approve($profile, $admin, [$key])[0]['approved']);
    }

    public function test_missing_photo_does_not_prevent_valid_bio_approval(): void
    {
        [$admin, $owner, $profile] = $this->fixture();
        $this->actingAs($owner)->post(route('account.photos.store'), [
            'photo' => UploadedFile::fake()->image('photo.jpg', 800, 800),
        ])->assertRedirect();
        $photo = $profile->photos()->with('latestVersion')->firstOrFail();
        $bio = $profile->bios()->create(['content' => 'Texto aprobado', 'status' => 'pending']);
        $service = app(PendingContentApproval::class);
        $keys = array_column($service->items($profile, $admin), 'key');
        Storage::disk('model_photos')->delete($photo->latestVersion->processed_path);
        $results = $service->approve($profile, $admin, $keys);
        $this->assertFalse($results[0]['approved']);
        $this->assertTrue($results[1]['approved']);
        $this->assertSame('approved', $bio->fresh()->status);
        $this->assertSame('pending', $photo->latestVersion->fresh()->status->value);
    }

    public function test_valid_photo_and_bio_are_approved_with_primary_and_audit(): void
    {
        [$admin, $owner, $profile] = $this->fixture();
        $this->actingAs($owner)->post(route('account.photos.store'), [
            'photo' => UploadedFile::fake()->image('photo.jpg', 800, 800),
        ]);
        $profile->bios()->create(['content' => 'Texto', 'status' => 'pending']);
        $service = app(PendingContentApproval::class);
        $result = $service->approve($profile, $admin, array_column($service->items($profile, $admin), 'key'));
        $this->assertSame([true, true], array_column($result, 'approved'));
        $this->assertTrue($profile->photos()->first()->is_primary);
        $this->assertSame($admin->id, $profile->photos()->first()->currentVersion->reviewed_by);
    }

    public function test_video_must_be_processed_and_existing_file_is_checked_again(): void
    {
        [$admin, , $profile] = $this->fixture();
        Storage::fake('model_videos');
        $token = (string) Str::uuid();
        $video = $profile->videos()->create([
            'public_token' => $token, 'resource_id' => $token, 'provider' => 'local',
            'original_path' => 'originals/'.$token.'/upload', 'original_name' => 'clip.mp4',
            'original_size' => 8, 'processing_status' => 'processing', 'moderation_status' => 'pending',
        ]);
        $service = app(PendingContentApproval::class);
        $this->assertSame([], $service->items($profile, $admin));
        $video->update(['processing_status' => 'ready', 'watermarked_at' => now()]);
        $key = $service->items($profile, $admin)[0]['key'];
        $this->assertFalse($service->approve($profile, $admin, [$key])[0]['approved']);
        Storage::disk('model_videos')->put('hosted/'.$token.'/video.mp4', 'video');
        Storage::disk('model_videos')->put('hosted/'.$token.'/poster.jpg', 'poster');
        $this->assertTrue($service->approve($profile, $admin, [$key])[0]['approved']);
        $this->assertSame($admin->id, $video->fresh()->reviewed_by);
    }

    public function test_new_photo_version_is_not_approved_using_old_selection(): void
    {
        [$admin, $owner, $profile] = $this->fixture();
        $this->actingAs($owner)->post(route('account.photos.store'), [
            'photo' => UploadedFile::fake()->image('photo.jpg', 800, 800),
        ]);
        $photo = $profile->photos()->firstOrFail();
        $service = app(PendingContentApproval::class);
        $key = $service->items($profile, $admin)[0]['key'];
        $this->post(route('account.photos.replace', $photo), [
            'photo' => UploadedFile::fake()->image('new.jpg', 800, 800),
        ])->assertRedirect();
        $this->assertFalse($service->approve($profile, $admin, [$key])[0]['approved']);
        $this->assertSame('pending', $photo->fresh()->latestVersion->status->value);
    }

    public function test_foreign_and_tampered_items_cannot_be_approved(): void
    {
        [$admin, , $profile] = $this->fixture();
        $other = User::factory()->create()->modelProfile;
        $bio = $profile->bios()->create(['content' => 'Texto', 'status' => 'pending']);
        $service = app(PendingContentApproval::class);
        $key = $service->items($profile, $admin)[0]['key'];
        $other->forceFill(['identity_status' => 'approved', 'review_status' => 'approved'])->save();
        $other->user->forceFill(['profile_completion_status' => AccountEnrollmentState::ACTIVE])->save();
        $this->assertFalse($service->approve($other, $admin, [$key])[0]['approved']);
        $this->assertFalse($service->approve($profile, $admin, [$key.'tampered'])[0]['approved']);
        $this->assertSame('pending', $bio->fresh()->status);
        $this->assertSame([], $service->items($profile, User::factory()->create()));
        $this->expectException(HttpException::class);
        $service->approve($profile, $profile->user, [$key]);
    }
}
