<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Log\Logger;
use Throwable;

class ApplicationInformationService
{
    private const CACHE_KEY = 'latestKoelVersion';

    public function __construct(private Client $client, private Cache $cache, private Logger $logger)
    {
    }

    /**
     * Get the latest version number of Koel from GitHub.
     */
    public function getLatestVersionNumber(): string
    {
        return $this->cache->remember(self::CACHE_KEY, now()->addDay(), function (): string {
            try {
                return json_decode($this->client->get('https://api.github.com/repos/koel/koel/tags')->getBody())[0]
                    ->name;
            } catch (Throwable $e) {
                $this->logger->error($e);

                return koel_version();
            }
        });
    }
}
