<?php

$base = dirname(__DIR__);
$source = $base . '/.htaccess.example';
$destination = $base . '/public/.htaccess';

if (!file_exists($destination) && !copy($source, $destination)) {
    fwrite(STDERR, "Failed to copy $source to $destination\n");
    exit(1);
}
