<?php

namespace App;

use Illuminate\Foundation\Application as IlluminateApplication;
use InvalidArgumentException;

/**
 * Extends \Illuminate\Foundation\Application to override some defaults.
 */
class Application extends IlluminateApplication
{
    /**
     * Loads a revision'ed asset file, making use of gulp-rev
     * This is a copycat of L5's Elixir, but catered to our directory structure.
     *
     * @param string $file
     *
     * @return string
     */
    public function rev($file)
    {
        static $manifest = null;

        if (is_null($manifest)) {
            $manifest = json_decode(file_get_contents($this->publicPath().'/build/rev-manifest.json'), true);
        }

        if (isset($manifest[$file])) {
            return "/public/build/{$manifest[$file]}";
        }

        throw new InvalidArgumentException("File {$file} not defined in asset manifest.");
    }
}
