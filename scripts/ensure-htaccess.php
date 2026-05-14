<?php

$base = dirname(__DIR__);
$target = $base . '/public/.htaccess';

if (!file_exists($target)) {
    copy($base . '/.htaccess.example', $target);
}
