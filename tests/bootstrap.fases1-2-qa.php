<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

$forwardedVariables = [
    'APP_ENV',
    'APP_MAINTENANCE_DRIVER',
    'BCRYPT_ROUNDS',
    'BROADCAST_CONNECTION',
    'CACHE_STORE',
    'DB_CONNECTION',
    'DB_HOST',
    'DB_PORT',
    'DB_DATABASE',
    'DB_URL',
    'MAIL_MAILER',
    'QUEUE_CONNECTION',
    'SESSION_DRIVER',
    'PULSE_ENABLED',
    'TELESCOPE_ENABLED',
    'NIGHTWATCH_ENABLED',
];

foreach ($forwardedVariables as $key) {
    if (! array_key_exists($key, $_SERVER)) {
        continue;
    }

    $value = (string) $_SERVER[$key];
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
}

if (($_SERVER['APP_ENV'] ?? null) !== 'testing') {
    throw new RuntimeException('Isolated Laravel QA bootstrap failed: APP_ENV is not testing.');
}

if (($_SERVER['DB_CONNECTION'] ?? null) !== 'mysql') {
    throw new RuntimeException('Isolated Laravel QA bootstrap failed: DB_CONNECTION is not mysql.');
}

if (($_SERVER['DB_HOST'] ?? null) !== 'mysql') {
    throw new RuntimeException('Isolated Laravel QA bootstrap failed: DB_HOST is not mysql.');
}

if (($_SERVER['DB_DATABASE'] ?? null) !== 'testing') {
    throw new RuntimeException('Isolated Laravel QA bootstrap failed: DB_DATABASE is not testing.');
}

if (($_SERVER['DB_URL'] ?? null) !== '') {
    throw new RuntimeException('Isolated Laravel QA bootstrap failed: DB_URL must be empty.');
}
