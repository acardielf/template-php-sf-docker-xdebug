<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Preserve APP_ENV set by PHPUnit (<server> element) before bootEnv() can override it
$appEnv = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null;

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    new Dotenv()->bootEnv(dirname(__DIR__).'/.env');
}

if (null !== $appEnv) {
    $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $appEnv;
}

if ($_SERVER['APP_DEBUG'] ?? true) {
    umask(0000);
}
