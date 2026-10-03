<?php

namespace Tests\Integration;

use App\Models\PublicationType;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\PublicModelProfileFixtures;

class PublicModelPerformanceTest extends OwnedMySqlSchema
{
    use PublicModelProfileFixtures;

    public function test_mysql_queries_image_requests_and_explain(): void
    {
        $this->migrateAll();
        $counts = [];
        foreach ([[1, 1], [5, 20]] as [$photos, $services]) {
            $slug = 'perf-'.$photos;
            $profile = $this->publicProfile($slug);
            $type = PublicationType::firstOrCreate(['slug' => 'virtual'], ['name' => 'Solo Virtual', 'is_active' => true]);
            $profile->update(['publication_type_id' => $type->id]);
            for ($i = 1; $i < $photos; $i++) {
                $this->publicPhoto($profile, $i);
            }
            for ($i = 0; $i < $services; $i++) {
                $profile->services()->attach(Service::factory()->create(['service_type' => 'virtual', 'is_active' => true])->id);
            }
            DB::flushQueryLog();
            DB::enableQueryLog();
            Model::preventLazyLoading();
            $start = hrtime(true);
            try {
                $response = $this->get('/modelos/'.$slug)->assertOk();
                $elapsed = (hrtime(true) - $start) / 1e6;
                $queries = DB::getQueryLog();
            } finally {
                DB::disableQueryLog();
                Model::preventLazyLoading(false);
            }
            $counts[] = count($queries);
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $images = $xpath->query('//section[@aria-labelledby="gallery-title"]//img');
            $urls = [];
            foreach ($images as $index => $img) {
                $this->assertGreaterThan(0, (int) $img->getAttribute('width'));
                $this->assertGreaterThan(0, (int) $img->getAttribute('height'));
                $url = $img->getAttribute('src');
                $this->assertSame($index === 0 || $url === $urls[0] ? 'eager' : 'lazy', $img->getAttribute('loading'));
                $urls[] = $url;
            }
            $this->assertSame('high', $images->item(0)->getAttribute('fetchpriority'));
            $this->assertCount($photos, array_unique($urls));
            $imageMs = [];
            foreach (array_unique($urls) as $url) {
                $start = hrtime(true);
                $image = $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/webp');
                $body = $image->streamedContent();
                $imageMs[] = (hrtime(true) - $start) / 1e6;
                $matched = false;
                foreach ($profile->photos()->with('currentVersion')->get() as $photo) {
                    $version = $photo->currentVersion;
                    $this->assertStringNotContainsString($version->thumbnail_path, $response->getContent());
                    if (str_contains($url, $version->public_token)) {
                        $this->assertSame(Storage::disk('model_photos')->get($version->public_path), $body);
                        $matched = true;
                    }
                }
                $this->assertTrue($matched);
            }
            fwrite(STDOUT, "\nT052 ".json_encode(['photos' => $photos, 'services' => $services, 'queries' => count($queries), 'http_ms' => round($elapsed, 2), 'sql_ms' => array_sum(array_column($queries, 'time')), 'image_requests' => count(array_unique($urls)), 'image_ms' => array_map(fn ($ms) => round($ms, 2), $imageMs)])."\n");
            if ($photos === 5) {
                foreach ($queries as $query) {
                    if (str_starts_with(strtolower($query['query']), 'select')) {
                        $plan = DB::select('EXPLAIN '.$query['query'], $query['bindings']);
                        fwrite(STDOUT, 'EXPLAIN '.json_encode(['sql' => $query['query'], 'plan' => $plan])."\n");
                    }
                }
            }
        }
        $this->assertSame($counts[0], $counts[1]);
    }
}
