<?php

namespace App\Services;

use App\Application;
use GuzzleHttp\Client;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Log\Logger;
use Throwable;

class ApplicationInformationService
{
    private const CACHE_KEY = 'latestKoelVersion';

    private $client;
    private $cache;
    private $logger;

    public function __construct(Client $client, Cache $cache, Logger $logger)
    {
        $this->client = $client;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Get the latest version number of Koel from GitHub.
     */
    public function getLatestVersionNumber(): string
    {
        return $this->cache->remember(self::CACHE_KEY, 1 * 24 * 60, function (): string {
            try {
                return json_decode(
                    $this->client->get('https://api.github.com/repos/phanan/koel/tags')->getBody()
                )[0]->name;
            } catch (Throwable $e) {
                $this->logger->error($e);

                return Application::KOEL_VERSION;
            }
        });
    }
}
