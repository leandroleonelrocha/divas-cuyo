<?php

namespace Tests\Unit\Services;

use App\Services\ModelProfileSlugService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ModelProfileSlugServiceTest extends TestCase
{
    public static function names(): array
    {
        return [[' Mía Cuyo! ', 'mia-cuyo'], ['123', 'modelo-123'], ['!!!', 'modelo'], ['', null], ['  ', null], [str_repeat('a', 200), str_repeat('a', 160)]];
    }

    #[DataProvider('names')]
    public function test_normalizes_only_public_name(string $name, ?string $expected): void
    {
        $this->assertSame($expected, (new ModelProfileSlugService)->base($name));
    }
}
