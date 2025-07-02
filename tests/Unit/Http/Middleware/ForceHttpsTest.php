<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\ForceHttps;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ForceHttpsTest extends TestCase
{
    private UrlGenerator|MockInterface $url;
    private ForceHttps $middleware;

    public function setUp(): void
    {
        parent::setUp();

        $this->url = Mockery::mock(UrlGenerator::class);
        $this->middleware = new ForceHttps($this->url);
    }

    #[Test]
    public function handle(): void
    {
        config(['koel.force_https' => true]);

        $this->url->expects('forceScheme')->with('https');

        $request = Mockery::mock(Request::class);
        $request->expects('getClientIp')->andReturn('127.0.0.1');
        $request->expects('setTrustedProxies')
            ->with(
                ['127.0.0.1'],
                Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
            );

        $response = Mockery::mock(Response::class);
        $next = static fn () => $response;

        self::assertSame($response, $this->middleware->handle($request, $next));
    }

    #[Test]
    public function notHandle(): void
    {
        config(['koel.force_https' => false]);

        $this->url->expects('forceScheme')->with('https')->never();

        $request = Mockery::mock(Request::class);
        $request->shouldNotReceive('setTrustedProxies');

        $response = Mockery::mock(Response::class);
        $next = static fn () => $response;

        self::assertSame($response, $this->middleware->handle($request, $next));
    }
}
