<?php

$base = dirname(__DIR__);
$target = $_ENV['LARAVEL_STORAGE_PATH'] ?? $_SERVER['LARAVEL_STORAGE_PATH'] ?? $base;
$target = rtrim($target, '/\\');

if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
    fwrite(STDERR, "Failed to create directory: $target\n");
    exit(1);
}

$source = $base . '/.env.example';
$destination = $target . '/.env';

if (!file_exists($destination) && !copy($source, $destination)) {
    fwrite(STDERR, "Failed to copy $source to $destination\n");
    exit(1);
}
