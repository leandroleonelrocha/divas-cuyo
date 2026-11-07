<?php

namespace App\Services;

use App\Models\ModelPhoto;
use App\Models\ModelProfile;
use App\Models\ModelProfileBio;
use App\Models\ModelProfilePhysicalRevision;
use App\Models\ModelVideo;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PendingContentApproval
{
    private const TYPES = [
        'photo' => [ModelPhoto::class, 'Fotografía', 'approve'],
        'video' => [ModelVideo::class, 'Video', 'moderate'],
        'bio' => [ModelProfileBio::class, 'Biografía', 'approve'],
        'physical' => [ModelProfilePhysicalRevision::class, 'Cambio físico', 'approve'],
    ];

    public function allowed(ModelProfile $profile, User $admin): bool
    {
        return $admin->can('update', $profile)
            && $profile->user?->profile_completion_status === AccountEnrollmentState::ACTIVE
            && $profile->identity_status === 'approved' && $profile->review_status === 'approved';
    }

    public function items(ModelProfile $profile, User $admin): array
    {
        if (! $this->allowed($profile, $admin)) {
            return [];
        }
        $items = [];
        foreach (self::TYPES as $type => [$class, $label, $ability]) {
            $query = $class::query()->where('model_profile_id', $profile->id);
            if ($type === 'photo') {
                $query->with('latestVersion')->whereHas('latestVersion', fn ($q) => $q->where('status', 'pending'));
            } elseif ($type === 'video') {
                $query->where('moderation_status', 'pending')->where('processing_status', 'ready')
                    ->whereNotNull('watermarked_at')->whereNotNull('resource_id');
            } else {
                $query->where('status', 'pending');
            }
            foreach ($query->orderBy('id')->get() as $record) {
                if (! $admin->can($ability, $record)) {
                    continue;
                }
                $item = ['key' => $this->key($profile, $type, $record), 'label' => $label.' #'.$record->id, 'type' => $type];
                if ($type === 'photo') {
                    $item['url'] = route('admin.model-photos.file', [$record, $record->latestVersion, 'processed']);
                } elseif ($type === 'video') {
                    $item['url'] = route('admin.model-videos.file', [$record, 'video']);
                } elseif ($type === 'bio') {
                    $item['text'] = $record->content;
                } else {
                    $labels = ['height_cm' => 'Altura (cm)', 'weight_kg' => 'Peso (kg)', 'measurements' => 'Medidas',
                        'eye_color' => 'Ojos', 'hair_color' => 'Cabello', 'skin_color' => 'Piel',
                        'body_type' => 'Contextura', 'nationality' => 'Nacionalidad'];
                    $item['text'] = collect($record->only(array_keys($labels)))
                        ->filter(fn ($value) => $value !== null && $value !== '')
                        ->map(fn ($value, $field) => $labels[$field].': '.$value)->join(' · ');
                }
                $items[] = $item;
            }
        }

        return $items;
    }

    private function key(ModelProfile $profile, string $type, Model $record): string
    {
        $snapshot = $record->getRawOriginal();
        if ($type === 'photo') {
            $snapshot['latest'] = $record->latestVersion?->getRawOriginal();
        }
        $fingerprint = hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
        $payload = $type.':'.$record->id.':'.$fingerprint;

        return $payload.':'.hash_hmac('sha256', $profile->id.':'.$payload, config('app.key'));
    }

    public function approve(ModelProfile $profile, User $admin, array $keys): array
    {
        abort_unless($this->allowed($profile->fresh('user'), $admin), 403);
        $results = [];
        foreach (array_unique($keys) as $key) {
            $parts = explode(':', (string) $key);
            $type = $parts[0] ?? '';
            $id = $parts[1] ?? '';
            $label = (self::TYPES[$type][1] ?? 'Elemento').' #'.$id;
            try {
                if (count($parts) !== 4 || ! isset(self::TYPES[$type]) || ! ctype_digit($id)) {
                    throw new \LogicException('La selección no es válida. Volvé a abrir el resumen.');
                }
                DB::transaction(function () use ($profile, $admin, $type, $id, $key) {
                    $lockedProfile = ModelProfile::query()->lockForUpdate()->findOrFail($profile->id);
                    abort_unless($this->allowed($lockedProfile, $admin), 403);
                    [$class, , $ability] = self::TYPES[$type];
                    $record = $class::query()->where('model_profile_id', $profile->id)->lockForUpdate()->findOrFail($id);
                    if ($type === 'photo') {
                        $record->setRelation('latestVersion', $record->latestVersion()->lockForUpdate()->first());
                    }
                    if (! hash_equals($this->key($lockedProfile, $type, $record), $key)) {
                        throw new \LogicException('Cambió desde que abriste el resumen. Revisalo nuevamente.');
                    }
                    Gate::forUser($admin)->authorize($ability, $record);
                    $pending = match ($type) {
                        'photo' => $record->latestVersion?->status?->value === 'pending',
                        'video' => $record->ready() && $record->moderation_status === 'pending',
                        default => $record->status === 'pending',
                    };
                    if (! $pending) {
                        throw new \LogicException('Ya no está pendiente o no está listo para revisión.');
                    }
                    match ($type) {
                        'photo' => app(ModelPhotoModerationService::class)->approve($record, $admin),
                        'video' => app(ModelVideoService::class)->moderate($admin, $record, 'approved'),
                        'bio' => app(ModelProfileBioModerationService::class)->approve($record, $admin),
                        'physical' => app(ModelProfilePhysicalModerationService::class)->approve($record, $admin),
                    };
                });
                $results[] = ['label' => $label, 'approved' => true, 'message' => 'Aprobado'];
            } catch (ValidationException $exception) {
                $results[] = ['label' => $label, 'approved' => false, 'message' => implode(' ', $exception->validator->errors()->all())];
            } catch (AuthorizationException|HttpException $exception) {
                $results[] = ['label' => $label, 'approved' => false, 'message' => 'Ya no tenés permiso o faltan requisitos para aprobarlo.'];
            } catch (ModelNotFoundException $exception) {
                $results[] = ['label' => $label, 'approved' => false, 'message' => 'El contenido ya no está disponible.'];
            } catch (\LogicException $exception) {
                $results[] = ['label' => $label, 'approved' => false, 'message' => $exception->getMessage()];
            } catch (\Throwable $exception) {
                report($exception);
                $results[] = ['label' => $label, 'approved' => false, 'message' => 'No se pudo aprobar. Revisá el archivo y volvé a intentar.'];
            }
        }

        return $results;
    }
}
