<?php

namespace App;

use Cache;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Foundation\Application as IlluminateApplication;
use InvalidArgumentException;
use Log;

/**
 * Extends \Illuminate\Foundation\Application to override some defaults.
 */
class Application extends IlluminateApplication
{
    /**
     * Current Koel version. Must start with a v, and is synced with git tags/releases.
     *
     * @link https://github.com/phanan/koel/releases
     */
    const KOEL_VERSION = 'v3.7.2';

    /**
     * We have merged public path and base path.
     *
     * @return string
     */
    public function publicPath()
    {
        return $this->basePath;
    }

    /**
     * Loads a revision'ed asset file, making use of gulp-rev
     * This is a copycat of L5's Elixir, but catered to our directory structure.
     *
     * @throws InvalidArgumentException
     */
    public function rev(string $file, string $manifestFile = null): string
    {
        static $manifest = null;

        $manifestFile = $manifestFile ?: public_path('public/mix-manifest.json');

        if ($manifest === null) {
            $manifest = json_decode(file_get_contents($manifestFile), true);
        }

        if (isset($manifest[$file])) {
            return file_exists(public_path('public/hot'))
                    ? "http://localhost:8080{$manifest[$file]}"
                    : $this->staticUrl("public{$manifest[$file]}");
        }

        throw new InvalidArgumentException("File {$file} not defined in asset manifest.");
    }

    /**
     * Get a URL for static file requests.
     * If this installation of Koel has a CDN_URL configured, use it as the base.
     * Otherwise, just use a full URL to the asset.
     *
     * @param string $name The additional resource name/path.
     */
    public function staticUrl(?string $name = null): string
    {
        $cdnUrl = trim(config('koel.cdn.url'), '/ ');

        return $cdnUrl ? $cdnUrl.'/'.trim(ltrim($name, '/')) : trim(asset($name));
    }

    /**
     * Get the latest version number of Koel from GitHub.
     */
    public function getLatestVersion(Client $client = null): string
    {
        return Cache::remember('latestKoelVersion', 1 * 24 * 60, static function () use ($client) {
            $client = $client ?: new Client();

            try {
                return json_decode(
                    $client->get('https://api.github.com/repos/phanan/koel/tags')->getBody()
                )[0]->name;
            } catch (Exception $e) {
                Log::error($e);

                return self::KOEL_VERSION;
            }
        });
    }
}
