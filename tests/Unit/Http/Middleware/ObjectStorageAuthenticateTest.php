<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\ObjectStorageAuthenticate;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ObjectStorageAuthenticateTest extends TestCase
{
    private ObjectStorageAuthenticate $middleware;

    public function setUp(): void
    {
        parent::setUp();

        $this->middleware = new ObjectStorageAuthenticate();
    }

    #[Test]
    public function passesWithMatchingAppKeyInQuery(): void
    {
        $request = Request::create('/api/os/s3/song?appKey=' . urlencode(config('app.key')), 'POST');
        $response = Mockery::mock(Response::class);

        self::assertSame($response, $this->middleware->handle($request, static fn () => $response));
    }

    #[Test]
    public function passesWithMatchingAppKeyInBody(): void
    {
        $request = Request::create('/api/os/s3/song', 'POST', ['appKey' => config('app.key')]);
        $response = Mockery::mock(Response::class);

        self::assertSame($response, $this->middleware->handle($request, static fn () => $response));
    }

    #[Test]
    public function passesWithMatchingAppKeyInJsonBody(): void
    {
        $request = Request::create(
            '/api/os/s3/song',
            'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['appKey' => config('app.key')]),
        );
        $response = Mockery::mock(Response::class);

        self::assertSame($response, $this->middleware->handle($request, static fn () => $response));
    }

    #[Test]
    public function rejectsMissingAppKey(): void
    {
        $this->assertUnauthorized(Request::create('/api/os/s3/song', 'POST'));
    }

    #[Test]
    public function rejectsWrongAppKey(): void
    {
        $this->assertUnauthorized(Request::create('/api/os/s3/song?appKey=wrong', 'POST'));
    }

    private function assertUnauthorized(Request $request): void
    {
        try {
            $this->middleware->handle($request, static fn () => Mockery::mock(Response::class));
            self::fail('Expected the request to be rejected.');
        } catch (HttpException $e) {
            self::assertSame(Response::HTTP_UNAUTHORIZED, $e->getStatusCode());
        }
    }
}
