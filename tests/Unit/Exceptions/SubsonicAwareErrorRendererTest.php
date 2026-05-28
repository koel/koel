<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\Contracts\SubsonicThrowable;
use App\Exceptions\OperationNotApplicableForSmartPlaylistException;
use App\Exceptions\SubsonicAwareErrorRenderer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;
use Throwable;

class SubsonicAwareErrorRendererTest extends TestCase
{
    #[Test]
    public function notFoundMapsToCode70(): void
    {
        $this->assertMapping(new NotFoundHttpException(), 70);
    }

    #[Test]
    public function accessDeniedMapsToCode50(): void
    {
        $this->assertMapping(new AccessDeniedHttpException(), 50);
    }

    #[Test]
    public function smartPlaylistOperationMapsToCode0(): void
    {
        $this->assertMapping(new OperationNotApplicableForSmartPlaylistException(), 0);
    }

    #[Test]
    public function validationMapsToCode10(): void
    {
        $this->assertMapping(ValidationException::withMessages(['x' => 'required']), 10);
    }

    #[Test]
    public function subsonicThrowableSuppliesItsOwnCodeAndMessage(): void
    {
        $exception = new class() extends Exception implements SubsonicThrowable {
            public function getSubsonicErrorCode(): int
            {
                return 42;
            }

            public function getSubsonicErrorMessage(): string
            {
                return 'Custom message.';
            }
        };

        $request = Request::create('/rest/ping.view?f=json', 'GET');
        $response = SubsonicAwareErrorRenderer::render($exception, $request);

        self::assertNotNull($response);
        $body = (string) $response->getContent();
        self::assertStringContainsString('"code":42', $body);
        self::assertStringContainsString('Custom message.', $body);
    }

    #[Test]
    public function returnsNullForNonRestRoutes(): void
    {
        $request = Request::create('/api/songs', 'GET');

        self::assertNull(SubsonicAwareErrorRenderer::render(new NotFoundHttpException(), $request));
    }

    #[Test]
    public function returnsNullForUnmappedExceptions(): void
    {
        $request = Request::create('/rest/ping.view', 'GET');

        self::assertNull(SubsonicAwareErrorRenderer::render(new RuntimeException('boom'), $request));
    }

    private function assertMapping(Throwable $exception, int $expectedCode): void
    {
        $request = Request::create('/rest/ping.view?f=json', 'GET');
        $response = SubsonicAwareErrorRenderer::render($exception, $request);

        self::assertNotNull($response);
        self::assertStringContainsString('"code":' . $expectedCode, (string) $response->getContent());
    }
}
