<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../vendor/autoload.php';

$expectedServerHostname = getenv('QA_EXPECTED_SERVER_HOSTNAME');
$expectedComposeProject = getenv('QA_EXPECTED_COMPOSE_PROJECT');
$expectedComposeService = getenv('QA_EXPECTED_COMPOSE_SERVICE');

if (! is_string($expectedServerHostname) || $expectedServerHostname === '') {
    fwrite(STDERR, "QA_PREFLIGHT_CONFIGURATION_ERROR missing expected MySQL hostname\n");
    exit(4);
}

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    $connection = DB::connection();
    $activeDatabase = DB::selectOne('select database() as database_name');
    $serverIdentity = DB::selectOne('select @@hostname as server_hostname');
    $connectionConfig = $connection->getConfig();

    $observed = [
        'app_env' => $app->environment(),
        'configuration_cached' => $app->configurationIsCached(),
        'default_connection' => DB::getDefaultConnection(),
        'effective_driver' => $connection->getDriverName(),
        'effective_host' => $connectionConfig['host'] ?? null,
        'effective_port' => (string) ($connectionConfig['port'] ?? ''),
        'effective_database' => $connectionConfig['database'] ?? null,
        'active_database' => $activeDatabase?->database_name,
        'server_hostname' => $serverIdentity?->server_hostname,
        'db_url_present_in_environment' => getenv('DB_URL') !== false && getenv('DB_URL') !== '',
        'db_url_present_in_laravel_config' => ! empty($connectionConfig['url'] ?? null),
        'expected_server_hostname' => $expectedServerHostname,
        'compose_project' => $expectedComposeProject,
        'compose_service' => $expectedComposeService,
    ];

    $expected = [
        'app_env' => 'testing',
        'configuration_cached' => false,
        'default_connection' => 'mysql',
        'effective_driver' => 'mysql',
        'effective_host' => 'mysql',
        'effective_port' => '3306',
        'effective_database' => 'testing',
        'active_database' => 'testing',
        'server_hostname' => $expectedServerHostname,
        'db_url_present_in_environment' => false,
        'db_url_present_in_laravel_config' => false,
        'expected_server_hostname' => $expectedServerHostname,
        'compose_project' => $expectedComposeProject,
        'compose_service' => 'mysql',
    ];

    $failures = [];

    foreach ($expected as $key => $value) {
        if (($observed[$key] ?? null) !== $value) {
            $failures[$key] = [
                'expected' => $value,
                'actual' => $observed[$key] ?? null,
            ];
        }
    }

    if ($failures !== []) {
        fwrite(STDERR, 'QA_PREFLIGHT_FAILED '.json_encode($failures, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);
        exit(2);
    }

    fwrite(STDOUT, 'QA_PREFLIGHT_OK '.json_encode($observed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'QA_PREFLIGHT_EXCEPTION '.json_encode([
        'class' => $exception::class,
        'message' => $exception->getMessage(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);
    exit(3);
}
