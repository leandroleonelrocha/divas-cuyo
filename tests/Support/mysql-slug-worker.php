<?php

use App\Models\ModelProfile;
use App\Services\ModelProfileSlugService;
use Illuminate\Database\Events\TransactionBeginning;
use Tests\Integration\MySqlTestCase;

// Independent PHP process: no inherited PDO or test transaction.
require dirname(__DIR__, 2).'/vendor/autoload.php';

$case = new class('worker') extends MySqlTestCase {};
$app = $case->createApplication();
$app['db']->statement('SET SESSION innodb_lock_wait_timeout = 1');
$attempts = 0;
$app['events']->listen(TransactionBeginning::class, function () use (&$attempts): void {
    $attempts++;
});
$profile = ModelProfile::findOrFail((int) $argv[1]);
echo "READY\n";
flush();
$deadline = microtime(true) + 10;
while (! file_exists($argv[3])) {
    if (microtime(true) > $deadline) {
        exit(3);
    }
    usleep(10000);
}
$started = microtime(true);
try {
    $result = $app->make(ModelProfileSlugService::class)->rename($profile, $argv[2]);
    echo json_encode(['connection_id' => $app['db']->selectOne('SELECT CONNECTION_ID() AS id')->id, 'slug' => $result->slug, 'attempts' => $attempts, 'seconds' => microtime(true) - $started])."\n";
} catch (Throwable $exception) {
    echo json_encode(['error' => get_class($exception), 'attempts' => $attempts, 'seconds' => microtime(true) - $started])."\n";
    exit(2);
}
