<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class ApplicationInformationService
{
    public function __construct(
        private readonly Client $client,
    ) {}

    public function getLatestVersionNumber(): string
    {
        return Cache::remember(
            cache_key('latest version number'),
            now()->addDay(),
            fn (): string => (
                rescue(
                    fn () => json_decode(
                        $this->client->get('https://api.github.com/repos/koel/koel/tags', [
                            'connect_timeout' => 3,
                            'timeout' => 5,
                        ])->getBody(),
                    )[0]->name,
                    report: false,
                ) ?? koel_version()
            ),
        );
    }
}
