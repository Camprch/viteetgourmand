<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if (isset($_SERVER['DATABASE_URL'])) {
    $normalizedDatabaseUrl = preg_replace('/\s+/', '', (string) $_SERVER['DATABASE_URL']);
    if (is_string($normalizedDatabaseUrl) && $normalizedDatabaseUrl !== '') {
        $_SERVER['DATABASE_URL'] = $normalizedDatabaseUrl;
        $_ENV['DATABASE_URL'] = $normalizedDatabaseUrl;
    }
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
