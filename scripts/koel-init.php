#!/usr/bin/env php
<?php

$args = array_slice($argv, 1);

passthru('composer install --ansi');
passthru('php artisan koel:init --ansi ' . implode(' ', $args));
