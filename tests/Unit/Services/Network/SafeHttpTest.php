<?php

namespace Tests\Unit\Services\Network;

use App\Exceptions\UnsafeUrlException;
use App\Services\Network\Network;
use App\Services\Network\SafeHttp;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SafeHttpTest extends TestCase
{
    private SafeHttp $safeHttp;

    public function setUp(): void
    {
        parent::setUp();

        $this->safeHttp = app(SafeHttp::class);
    }

    #[Test]
    public function headRejectsPrivateIpLiteral(): void
    {
        $this->expectException(UnsafeUrlException::class);

        $this->safeHttp->head('http://127.0.0.1/admin');
    }

    #[Test]
    public function getRejectsPrivateIpLiteral(): void
    {
        $this->expectException(UnsafeUrlException::class);

        $this->safeHttp->get('http://169.254.169.254/latest/meta-data/');
    }

    #[Test]
    public function headRejectsRedirectToPrivateHost(): void
    {
        Http::fake([
            '8.8.8.8/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/admin']),
            '*' => Http::response('', 200),
        ]);

        $this->expectException(UnsafeUrlException::class);

        $this->safeHttp->head('https://8.8.8.8/feed');
    }

    #[Test]
    public function headFollowsRedirectsAcrossPublicHosts(): void
    {
        Http::fake([
            '8.8.8.8/*' => Http::response('', 302, ['Location' => 'https://1.1.1.1/feed']),
            '1.1.1.1/*' => Http::response('', 200),
        ]);

        self::assertTrue($this->safeHttp->head('https://8.8.8.8/feed')->successful());
    }

    #[Test]
    public function pinsEveryValidatedIpOfAHostInASingleResolveEntry(): void
    {
        $network = Mockery::mock(Network::class);
        $network->expects('resolveToPublicIps')->with('podcast.test')->andReturn(['23.32.242.163', '2a02:26f0::1']);

        $resolveEntries = null;

        Http::fake(static function (Request $request, array $options) use (&$resolveEntries): PromiseInterface {
            $resolveEntries = $options['curl'][CURLOPT_RESOLVE];

            return Http::response();
        });

        (new SafeHttp($network))->get('https://podcast.test/feed');

        self::assertSame(['podcast.test:443:23.32.242.163,[2a02:26f0::1]'], $resolveEntries);
    }

    #[Test]
    public function pinnedGuzzleClientRejectsPrivateIp(): void
    {
        // The pinned Guzzle client (used by Poddle, getStreamableUrl) shares
        // the same pin-and-validate middleware. Verify the standalone branch
        // throws on a private target, not just the Laravel Http path.
        $this->expectException(UnsafeUrlException::class);

        $this->safeHttp->getPinnedGuzzleClient('http://127.0.0.1/feed')->request('GET', 'http://127.0.0.1/feed');
    }
}
