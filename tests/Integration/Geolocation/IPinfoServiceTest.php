<?php

namespace Tests\Integration\Geolocation;

use App\Http\Integrations\IPinfo\Requests\GetLiteDataRequest;
use App\Services\Geolocation\IPinfoService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Tests\TestCase;

use function Tests\test_path;

class IPinfoServiceTest extends TestCase
{
    private IPinfoService $service;

    public function setUp(): void
    {
        parent::setUp();

        config(['koel.services.ipinfo.token' => 'ipinfo-token']);
        $this->service = app(IPinfoService::class);
    }

    #[Test]
    public function getCountryCodeFromIp(): void
    {
        Saloon::fake([
            GetLiteDataRequest::class => MockResponse::make(
                body: File::json(test_path('fixtures/ipinfo/lite-data.json')),
            ),
        ]);

        $countryCode = $this->service->getCountryCodeFromIp('172.16.31.10');

        self::assertSame('DE', $countryCode);
        Saloon::assertSent(static fn (GetLiteDataRequest $request) => $request->ip === '172.16.31.10');
    }

    #[Test]
    public function getCountryCodeFromCache(): void
    {
        Saloon::fake([]);
        Cache::put(cache_key('IP to country code', '172.16.31.10'), 'VN', now()->addDay());

        self::assertSame('VN', $this->service->getCountryCodeFromIp('172.16.31.10'));
        Saloon::assertNothingSent();
    }
}
