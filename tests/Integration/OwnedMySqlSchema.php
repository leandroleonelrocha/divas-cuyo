<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class OwnedMySqlSchema extends MySqlTestCase
{
    private bool $ownsSchema = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::select('SHOW TABLES') !== []) {
            throw new RuntimeException('Integration requires an EMPTY isolated database; existing tables will not be removed.');
        }
        $this->ownsSchema = true;
        $this->withoutVite();
        config(['hashing.bcrypt.rounds' => 4]);
    }

    protected function migrateAll(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        try {
            if ($this->ownsSchema) {
                $this->artisan('migrate:reset', ['--force' => true])->assertExitCode(0);
                DB::statement('DROP TABLE IF EXISTS migrations');
            }
        } finally {
            parent::tearDown();
        }
    }
}
