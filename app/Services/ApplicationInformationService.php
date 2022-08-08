<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Contracts\Cache\Repository as Cache;

class ApplicationInformationService
{
    public function __construct(private Client $client, private Cache $cache)
    {
    }

    /**
     * Get the latest version number of Koel from GitHub.
     */
    public function getLatestVersionNumber(): string
    {
        return attempt(function () {
            return $this->cache->remember('latestKoelVersion', now()->addDay(), function (): string {
                return json_decode($this->client->get('https://api.github.com/repos/koel/koel/tags')->getBody())[0]
                    ->name;
            });
        }) ?? koel_version();
    }
}
