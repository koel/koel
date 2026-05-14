<?php

$base = dirname(__DIR__);
$target = getenv('LARAVEL_STORAGE_PATH') ?: $base;
$target = rtrim($target, '/\\');

if (!is_dir($target)) {
    @mkdir($target, 0755, true);
}

if (!file_exists($target . '/.env')) {
    copy($base . '/.env.example', $target . '/.env');
}
