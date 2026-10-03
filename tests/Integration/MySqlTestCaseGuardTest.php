<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MySqlTestCaseGuardTest extends TestCase
{
    public function test_mysql_integration_requires_external_connection_settings(): void
    {
        $environmentNames = self::environmentNames();
        $previous = self::captureEnvironment($environmentNames);

        try {
            foreach ($environmentNames as $name) {
                putenv($name);
            }

            $testCase = new class('mysql guard') extends MySqlTestCase
            {
                protected function runTest(): void {}
            };

            try {
                $testCase->createApplication();
                $this->fail('Missing test database configuration must be rejected before boot.');
            } catch (RuntimeException $exception) {
                $this->assertSame(
                    'MySQL integration requires externally configured PUBLIC_PROFILE_MYSQL_TEST_HOST.',
                    $exception->getMessage(),
                );
            }
        } finally {
            self::restoreEnvironment($previous);
        }
    }

    public function test_mysql_integration_rejects_non_test_database_names_before_boot(): void
    {
        $environmentNames = self::environmentNames();
        $previous = self::captureEnvironment($environmentNames);

        try {
            foreach ([
                'PUBLIC_PROFILE_MYSQL_TEST_HOST' => '127.0.0.1',
                'PUBLIC_PROFILE_MYSQL_TEST_PORT' => '3306',
                'PUBLIC_PROFILE_MYSQL_TEST_DATABASE' => 'divas_cuyo',
                'PUBLIC_PROFILE_MYSQL_TEST_USERNAME' => 'test-user',
                'PUBLIC_PROFILE_MYSQL_TEST_PASSWORD' => '',
            ] as $name => $value) {
                putenv($name.'='.$value);
            }

            $testCase = new class('mysql guard') extends MySqlTestCase
            {
                protected function runTest(): void {}
            };

            try {
                $testCase->createApplication();
                $this->fail('A non-test database must be rejected before boot.');
            } catch (RuntimeException $exception) {
                $this->assertSame('MySQL integration database name must end in _test.', $exception->getMessage());
            }
        } finally {
            self::restoreEnvironment($previous);
        }
    }

    public function test_mysql_integration_case_does_not_use_refresh_database_transactions(): void
    {
        $traits = class_uses_recursive(MySqlTestCase::class);

        $this->assertArrayNotHasKey(RefreshDatabase::class, $traits);
    }

    /** @return list<string> */
    private static function environmentNames(): array
    {
        return [
            'PUBLIC_PROFILE_MYSQL_TEST_HOST',
            'PUBLIC_PROFILE_MYSQL_TEST_PORT',
            'PUBLIC_PROFILE_MYSQL_TEST_DATABASE',
            'PUBLIC_PROFILE_MYSQL_TEST_USERNAME',
            'PUBLIC_PROFILE_MYSQL_TEST_PASSWORD',
        ];
    }

    /** @param list<string> $names
     * @return array<string, string|false>
     */
    private static function captureEnvironment(array $names): array
    {
        $values = [];
        foreach ($names as $name) {
            $values[$name] = getenv($name);
        }

        return $values;
    }

    /** @param array<string, string|false> $values */
    private static function restoreEnvironment(array $values): void
    {
        foreach ($values as $name => $value) {
            if ($value === false) {
                putenv($name);
            } else {
                putenv($name.'='.$value);
            }
        }
    }
}
