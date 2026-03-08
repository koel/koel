<?php

namespace Tests\Unit\Rules;

use App\Rules\ValidRadioStationUrl;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValidRadioStationUrlTest extends TestCase
{
    #[Test]
    public function valid(): void
    {
        Http::fake([
            '*' => Http::response('', 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        (new ValidRadioStationUrl())->validate(
            'url',
            'https://example.com/stream',
            fn (string $attribute, ?string $message) => $this->fail("Validation failed for $attribute: $message"), // @phpstan-ignore-line
        );

        $this->addToAssertionCount(1);
    }

    /** @return array<mixed> */
    public static function provideInvalidContentType(): array
    {
        return [
            'invalid content type' => ['text/html'],
            'no content type' => [null],
            'empty content type' => [''],
        ];
    }

    #[Test, DataProvider('provideInvalidContentType')]
    public function invalidBecauseOfNullHeader(?string $contentType): void
    {
        Http::fake([
            '*' => Http::response('', 200, ['Content-Type' => $contentType]),
        ]);

        (new ValidRadioStationUrl())->validate(
            'url',
            'https://example.com/stream',
            fn () => $this->addToAssertionCount(1), // @phpstan-ignore-line
        );
    }

    /** @return array<mixed> */
    public static function providePrivateUrls(): array
    {
        return [
            'localhost' => ['http://localhost/stream'],
            'private IPv4' => ['http://192.168.1.1/stream'],
            'loopback IPv4' => ['http://127.0.0.1/stream'],
            'link-local IPv4' => ['http://169.254.1.1/stream'],
            'private 10.x' => ['http://10.0.0.1/stream'],
            'loopback IPv6' => ['http://[::1]/stream'],
        ];
    }

    #[Test, DataProvider('providePrivateUrls')]
    public function rejectsPrivateUrls(string $url): void
    {
        Http::fake([
            '*' => Http::response('', 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        $failed = false;

        (new ValidRadioStationUrl())->validate('url', $url, static function () use (&$failed) { // @phpstan-ignore-line
            $failed = true;
        });

        self::assertTrue($failed, "Expected validation to fail for private URL: $url");
        Http::assertNothingSent();
    }

    #[Test]
    public function bypass(): void
    {
        Http::fake();

        $rule = new ValidRadioStationUrl();
        $rule->bypass = true;

        $rule->validate('url', 'https://example.com/stream', static fn () => null); // @phpstan-ignore-line

        $this->addToAssertionCount(1);
        Http::assertNothingSent();
    }
}
