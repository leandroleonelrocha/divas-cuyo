<?php

namespace App\Services;

use App\Enums\ModelPhotoVersionStatus;
use App\Models\ModelProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Explica, punto por punto, qué le falta a un perfil para que su publicación
 * aparezca en el sitio público. Espeja las condiciones de
 * PublicModelProfileVisibility (candidates/publiclyVisible) en lenguaje de la modelo.
 */
class PublicProfileReadinessService
{
    public function __construct(private readonly ModelPhotoStorage $storage) {}

    /**
     * @return Collection<int, array{label: string, href: string|null, action: string|null}>
     */
    public function missing(ModelProfile $profile): Collection
    {
        $items = collect();

        if (blank($profile->stage_name) || blank($profile->slug)) {
            $items->push([
                'label' => 'Falta tu nombre artístico (el nombre público con el que te van a encontrar)',
                'href' => route('account.profile.edit'),
                'action' => 'Completar nombre',
            ]);
        }

        if ($profile->identity_status !== 'approved') {
            $items->push([
                'label' => match ($profile->identity_status) {
                    'pending' => 'Tu identidad todavía no fue aprobada por el equipo',
                    'rejected' => 'Tu identidad fue rechazada: corregí la documentación',
                    default => 'Falta completar y enviar tu documentación de identidad',
                },
                'href' => $profile->user ? route('identity.show', $profile->user) : null,
                'action' => 'Ver identidad',
            ]);
        }

        if ($photo = $this->missingPrimaryPhoto($profile)) {
            $items->push($photo);
        }

        if ($profile->review_status !== 'approved') {
            $items->push([
                'label' => match ($profile->review_status) {
                    'pending' => 'Tu perfil todavía está en revisión por el equipo',
                    'rejected' => 'Tu perfil fue rechazado en la revisión',
                    default => 'Tu perfil todavía no fue revisado por el equipo',
                },
                'href' => $profile->review_status === 'rejected' ? route('account.profile.edit') : null,
                'action' => 'Revisar mi perfil',
            ]);
        }

        $paymentIsValid = $profile->currentPayment !== null && ! $profile->currentPayment->expires_at->isPast();
        if (! $paymentIsValid && ($profile->payments->isNotEmpty() || ! $profile->is_published)) {
            $items->push([
                'label' => $profile->payments->isNotEmpty()
                    ? 'Tu pago de publicación está vencido: renovalo para volver a aparecer'
                    : 'Falta el pago de publicación',
                'href' => '#instrucciones-pago',
                'action' => 'Ver instrucciones de pago',
            ]);
        }

        if (! $profile->is_published) {
            $items->push([
                'label' => 'Tu perfil todavía no está publicado en el sitio',
                'href' => null,
                'action' => null,
            ]);
        }

        return $items;
    }

    public function isReady(ModelProfile $profile): bool
    {
        return $this->missing($profile)->isEmpty();
    }

    /**
     * @return array{label: string, href: string, action: string}|null
     */
    private function missingPrimaryPhoto(ModelProfile $profile): ?array
    {
        $primaries = $profile->photos()->where('is_primary', true)->with('currentVersion')->get();

        if ($primaries->isEmpty()) {
            return ['label' => 'Falta marcar una foto como principal', 'href' => route('account.photos.index'), 'action' => 'Elegir foto principal'];
        }

        if ($primaries->count() > 1) {
            return ['label' => 'Hay más de una foto principal marcada: dejá sólo una', 'href' => route('account.photos.index'), 'action' => 'Revisar mis fotos'];
        }

        $photo = $primaries->first();
        $version = $photo->currentVersion;

        if (! $version || $version->model_photo_id !== $photo->getKey()) {
            return ['label' => 'Tu foto principal todavía se está procesando', 'href' => route('account.photos.index'), 'action' => 'Ver mis fotos'];
        }

        if ($version->status === ModelPhotoVersionStatus::Pending) {
            return ['label' => 'Tu foto principal está esperando aprobación', 'href' => route('account.photos.index'), 'action' => 'Ver mis fotos'];
        }

        if ($version->status === ModelPhotoVersionStatus::Rejected) {
            return ['label' => 'Tu foto principal fue rechazada: elegí otra', 'href' => route('account.photos.index'), 'action' => 'Elegir otra foto'];
        }

        if ($version->public_watermarked_at === null || ! Str::isUuid($version->public_token ?? '')) {
            return ['label' => 'Tu foto principal todavía no está lista para publicarse', 'href' => route('account.photos.index'), 'action' => 'Ver mis fotos'];
        }

        $stream = $this->storage->openPublicStream($version->public_path ?? '');
        if (! is_resource($stream)) {
            return ['label' => 'Tu foto principal todavía no está lista para publicarse', 'href' => route('account.photos.index'), 'action' => 'Ver mis fotos'];
        }
        fclose($stream);

        return null;
    }
}
