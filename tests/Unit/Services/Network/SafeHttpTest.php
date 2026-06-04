<?php

namespace Tests\Unit\Services\Network;

use App\Exceptions\UnsafeUrlException;
use App\Services\Network\SafeHttp;
use Illuminate\Support\Facades\Http;
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
}
