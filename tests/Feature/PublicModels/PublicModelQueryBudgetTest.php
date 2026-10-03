<?php

namespace Tests\Feature\PublicModels;

use App\Models\PublicationType;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicModelQueryBudgetTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_public_query_count_stays_constant_for_one_or_five_photos_and_one_or_twenty_services(): void
    {
        $singleResourceQueries = $this->queryCountForProfile('mia-small', 1, 1);
        $fullResourceQueries = $this->queryCountForProfile('mia-full', 5, 20);

        $this->assertSame(
            $singleResourceQueries,
            $fullResourceQueries,
            'Public profile queries grew with photo or service counts.',
        );
    }

    private function queryCountForProfile(string $slug, int $photoCount, int $serviceCount): int
    {
        $profile = $this->publicProfile($slug);
        $publicationType = PublicationType::factory()->create([
            'slug' => 'virtual-'.$slug,
            'name' => 'Solo Virtual',
        ]);
        $profile->update(['publication_type_id' => $publicationType->id]);

        for ($position = 1; $position < $photoCount; $position++) {
            $this->publicPhoto($profile, $position);
        }

        $serviceIds = [];
        for ($index = 0; $index < $serviceCount; $index++) {
            $service = Service::factory()->create([
                'name' => 'Servicio '.$slug.' '.$index,
                'slug' => 'servicio-'.$slug.'-'.$index,
                'service_type' => 'virtual',
                'is_active' => true,
                'sort_order' => $index,
            ]);
            $serviceIds[] = $service->id;
        }
        $profile->services()->attach($serviceIds);

        $connection = DB::connection();
        $previousLazyLoading = Model::preventsLazyLoading();
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        Model::preventLazyLoading();

        try {
            $this->get('/modelos/'.$slug)->assertOk();
            $queries = $connection->getQueryLog();
        } finally {
            Model::preventLazyLoading($previousLazyLoading);
            $connection->disableQueryLog();
        }

        return count(array_filter($queries, static fn (array $query): bool => ! str_starts_with(strtolower(ltrim($query['query'])), 'pragma ')
        ));
    }
}
