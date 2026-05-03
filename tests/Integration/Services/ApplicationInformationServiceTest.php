<?php

namespace Tests\Integration\Services;

use App\Services\ApplicationInformationService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class ApplicationInformationServiceTest extends TestCase
{
    #[Test]
    public function getLatestVersionNumber(): void
    {
        $latestVersion = 'v1.1.2';

        $mock = new MockHandler([
            new Response(200, [], File::get(test_path('fixtures/github-tags.json'))),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $service = new ApplicationInformationService($client);

        self::assertSame($latestVersion, $service->getLatestVersionNumber());
        self::assertSame($latestVersion, cache()->get(cache_key('latest version number')));
    }

    #[Test]
    public function fallsBackToCurrentVersionWhenGitHubIsUnreachable(): void
    {
        $mock = new MockHandler([
            new ConnectException('cURL error 7: Failed to connect', new Request('GET', 'tags')),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $service = new ApplicationInformationService($client);

        self::assertSame(koel_version(), $service->getLatestVersionNumber());
        self::assertSame(koel_version(), cache()->get(cache_key('latest version number')));
    }
}
