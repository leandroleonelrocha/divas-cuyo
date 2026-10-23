<?php

namespace App\Contracts;

use Symfony\Component\HttpFoundation\Response;

interface VideoHostingProvider
{
    /** Store only watermarked MP4 and its poster, privately. The resource ID is an idempotency key. */
    public function store(string $resourceId, string $videoPath, string $posterPath): void;

    public function available(string $resourceId): bool;

    /** Called only after authorization. Remote implementations must use short-lived signed delivery. */
    public function response(string $resourceId, string $variant): Response;

    public function delete(string $resourceId): void;

    /** Export processed assets for migration, never the original upload. */
    public function export(string $resourceId, string $directory): void;
}
