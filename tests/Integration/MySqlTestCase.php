<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use RuntimeException;

abstract class MySqlTestCase extends LaravelTestCase
{
    public function createApplication()
    {
        $settings = self::mysqlTestSettings();
        foreach ($settings as $name => $value) {
            putenv($name.'='.$value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }

        if (file_exists(dirname(__DIR__, 2).'/bootstrap/cache/config.php')) {
            throw new RuntimeException('Remove cached configuration before isolated MySQL integration.');
        }

        $application = parent::createApplication();
        $application['config']->set('database.default', 'mysql');
        $application['config']->set('database.connections.mysql.host', $settings['DB_HOST']);
        $application['config']->set('database.connections.mysql.port', $settings['DB_PORT']);
        $application['config']->set('database.connections.mysql.database', $settings['DB_DATABASE']);
        $application['config']->set('database.connections.mysql.username', $settings['DB_USERNAME']);
        $application['config']->set('database.connections.mysql.password', $settings['DB_PASSWORD']);
        $application['config']->set('database.connections.mysql.url', null);
        $application['config']->set('database.connections.mysql.unix_socket', '');
        $application['db']->purge('mysql');
        $actual = $application['db']->connection('mysql')->selectOne('SELECT DATABASE() AS name')->name;
        if ($actual !== $settings['DB_DATABASE'] || ! str_ends_with($actual, '_test')) {
            throw new RuntimeException('Connected database failed the _test safety guard.');
        }

        return $application;
    }

    /** @return array<string, string> */
    private static function mysqlTestSettings(): array
    {
        $variables = [
            'DB_HOST' => 'PUBLIC_PROFILE_MYSQL_TEST_HOST',
            'DB_PORT' => 'PUBLIC_PROFILE_MYSQL_TEST_PORT',
            'DB_DATABASE' => 'PUBLIC_PROFILE_MYSQL_TEST_DATABASE',
            'DB_USERNAME' => 'PUBLIC_PROFILE_MYSQL_TEST_USERNAME',
            'DB_PASSWORD' => 'PUBLIC_PROFILE_MYSQL_TEST_PASSWORD',
        ];
        $settings = [];

        foreach ($variables as $configName => $environmentName) {
            $value = getenv($environmentName);
            if ($value === false || ($configName !== 'DB_PASSWORD' && $value === '')) {
                throw new RuntimeException('MySQL integration requires externally configured '.$environmentName.'.');
            }
            $settings[$configName] = $value;
        }

        if (! str_ends_with($settings['DB_DATABASE'], '_test')) {
            throw new RuntimeException('MySQL integration database name must end in _test.');
        }

        $settings['DB_CONNECTION'] = 'mysql';
        $settings['DB_URL'] = '';
        $settings['DB_SOCKET'] = '';
        $settings['APP_ENV'] = 'testing';
        $settings['CACHE_STORE'] = 'array';
        $settings['SESSION_DRIVER'] = 'array';
        $settings['QUEUE_CONNECTION'] = 'sync';

        return $settings;
    }
}
