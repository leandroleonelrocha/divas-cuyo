<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Crea el historial de notificaciones a partir de las decisiones ya tomadas
 * por el admin (columnas reviewed_at / status de cada tabla). Todas quedan
 * marcadas como leídas para que el contador arranque en cero.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->backfillFromProfiles();
        $this->backfillFromPhotos();
        $this->backfillFromBios();
        $this->backfillFromPhysicalRevisions();
        $this->backfillFromVideos();
    }

    public function down(): void
    {
        // Migración de una sola vez: el histórico no se puede distinguir de
        // las notificaciones nuevas, así que no se revierte.
    }

    private function backfillFromProfiles(): void
    {
        $rows = DB::table('model_profiles')
            ->whereNotNull('identity_reviewed_at')
            ->whereIn('identity_status', ['approved', 'rejected'])
            ->get(['id', 'identity_status', 'identity_reviewed_at', 'identity_rejection_reason']);

        $this->insert($rows->map(fn ($row) => $this->entry(
            $row->id,
            'identity_'.$row->identity_status,
            $row->identity_rejection_reason,
            $row->identity_reviewed_at,
        )));

        $rows = DB::table('model_profiles')
            ->whereNotNull('reviewed_at')
            ->whereIn('review_status', ['approved', 'rejected'])
            ->get(['id', 'review_status', 'reviewed_at']);

        $this->insert($rows->map(fn ($row) => $this->entry(
            $row->id,
            'profile_'.$row->review_status,
            null,
            $row->reviewed_at,
        )));
    }

    private function backfillFromPhotos(): void
    {
        $rows = DB::table('model_photo_versions as v')
            ->join('model_photos as p', 'p.id', '=', 'v.model_photo_id')
            ->whereNotNull('v.reviewed_at')
            ->whereIn('v.status', ['approved', 'rejected'])
            ->get(['p.model_profile_id as profile_id', 'v.status', 'v.reviewed_at', 'v.rejection_reason']);

        $this->insert($rows->map(fn ($row) => $this->entry(
            $row->profile_id,
            'photo_'.$row->status,
            $row->rejection_reason,
            $row->reviewed_at,
        )));
    }

    private function backfillFromBios(): void
    {
        $rows = DB::table('model_profile_bios')
            ->whereNotNull('reviewed_at')
            ->whereIn('status', ['approved', 'rejected'])
            ->get(['model_profile_id', 'status', 'reviewed_at', 'rejection_reason']);

        $this->insert($rows->map(fn ($row) => $this->entry(
            $row->model_profile_id,
            'bio_'.$row->status,
            $row->rejection_reason,
            $row->reviewed_at,
        )));
    }

    private function backfillFromPhysicalRevisions(): void
    {
        $rows = DB::table('model_profile_physical_revisions')
            ->whereNotNull('reviewed_at')
            ->whereIn('status', ['approved', 'rejected'])
            ->get(['model_profile_id', 'status', 'reviewed_at', 'rejection_reason']);

        $this->insert($rows->map(fn ($row) => $this->entry(
            $row->model_profile_id,
            'physical_'.$row->status,
            $row->rejection_reason,
            $row->reviewed_at,
        )));
    }

    private function backfillFromVideos(): void
    {
        $rows = DB::table('model_videos')
            ->whereNotNull('reviewed_at')
            ->whereIn('moderation_status', ['approved', 'rejected'])
            ->get(['model_profile_id', 'moderation_status', 'reviewed_at', 'rejection_reason']);

        $this->insert($rows->map(fn ($row) => $this->entry(
            $row->model_profile_id,
            'video_'.$row->moderation_status,
            $row->rejection_reason,
            $row->reviewed_at,
        )));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function insert($rows): void
    {
        foreach ($rows->chunk(200) as $chunk) {
            DB::table('model_notifications')->insert($chunk->all());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(int $profileId, string $type, ?string $detail, string $createdAt): array
    {
        return [
            'model_profile_id' => $profileId,
            'type' => $type,
            'detail' => $detail,
            'read_at' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
};
