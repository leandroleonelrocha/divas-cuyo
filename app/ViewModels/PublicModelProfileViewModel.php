<?php

namespace App\ViewModels;

final readonly class PublicModelProfileViewModel
{
    /**
     * @param  list<string>  $location
     * @param  array<string, string>  $characteristics  Formatted public values only; hidden age is omitted.
     * @param  array<string, list<string>>  $serviceGroups  Active compatible service names only.
     */
    public function __construct(
        public string $stageName,
        public string $canonicalUrl,
        public string $verifiedLabel,
        public string $availabilityLabel,
        public array $location,
        public array $primaryPhoto,
        public array $photos,
        public array $characteristics = [],
        public ?string $bio = null,
        public ?string $publicationTypeLabel = null,
        public array $serviceGroups = [],
        public string $title = '',
        public string $description = '',
    ) {}
}
