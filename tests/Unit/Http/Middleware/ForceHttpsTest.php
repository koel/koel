<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\ForceHttps;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ForceHttpsTest extends TestCase
{
    private $url;
    private ForceHttps $middleware;

    public function setUp(): void
    {
        parent::setUp();

        $this->url = Mockery::mock(UrlGenerator::class);
        $this->middleware = new ForceHttps($this->url);
    }

    public function testHandle(): void
    {
        config(['koel.force_https' => true]);

        $this->url->shouldReceive('forceScheme')->with('https');

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getClientIp')->andReturn('127.0.0.1');
        $request->shouldReceive('setTrustedProxies')
            ->with(
                ['127.0.0.1'],
                Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
            );

        $response = Mockery::mock(Response::class);

        $next = static function () use ($response) {
            return $response;
        };

        self::assertSame($response, $this->middleware->handle($request, $next));
    }

    public function testNotHandle(): void
    {
        config(['koel.force_https' => false]);

        $this->url->shouldReceive('forceScheme')->with('https')->never();

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('setTrustedProxies')->never();

        $response = Mockery::mock(Response::class);

        $next = static function () use ($response) {
            return $response;
        };

        self::assertSame($response, $this->middleware->handle($request, $next));
    }
}
