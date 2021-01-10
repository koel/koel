<?php

namespace App;

use Illuminate\Foundation\Application as IlluminateApplication;

/**
 * Extends \Illuminate\Foundation\Application to override some defaults.
 */
class Application extends IlluminateApplication
{
    /**
     * Current Koel version. Must start with a v, and is synced with git tags/releases.
     *
     * @see https://github.com/koel/koel/releases
     */
    public const KOEL_VERSION = 'v5.0.1';
}
