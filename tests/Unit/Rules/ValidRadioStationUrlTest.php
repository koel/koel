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
    public static function provideValidContentTypes(): array
    {
        return [
            'audio/mpeg' => ['audio/mpeg'],
            'audio/ogg' => ['audio/ogg'],
            'audio/mp4' => ['audio/mp4'],
            'application/ogg' => ['application/ogg'],
            'application/x-mpegurl' => ['application/x-mpegurl'],
            'application/vnd.apple.mpegurl' => ['application/vnd.apple.mpegurl'],
            'video/mp2t' => ['video/mp2t'],
            'with charset' => ['audio/mpeg; charset=utf-8'],
        ];
    }

    #[Test, DataProvider('provideValidContentTypes')]
    public function validContentTypes(string $contentType): void
    {
        Http::fake([
            '*' => Http::response('', 200, ['Content-Type' => $contentType]),
        ]);

        (new ValidRadioStationUrl())->validate(
            'url',
            'https://example.com/stream',
            fn (string $attribute, ?string $message) => $this->fail("Validation failed for $attribute: $message"), // @phpstan-ignore-line
        );

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function fallsBackToGetWhenHeadFails(): void
    {
        Http::fake([
            'example.com/stream' => function ($request) {
                if ($request->method() === 'HEAD') {
                    return Http::response('', 405); // Method not allowed
                }
                return Http::response('', 200, ['Content-Type' => 'audio/mpeg']);
            },
        ]);

        (new ValidRadioStationUrl())->validate(
            'url',
            'https://example.com/stream',
            fn (string $attribute, ?string $message) => $this->fail("Validation failed for $attribute: $message"), // @phpstan-ignore-line
        );

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function acceptsUrlWhenServerRespondsEvenWithInvalidContentType(): void
    {
        // Some streaming servers respond but don't return valid Content-Type
        // We're more permissive now - if server responds, we accept it
        Http::fake([
            '*' => Http::response('', 200, ['Content-Type' => 'text/html']),
        ]);

        (new ValidRadioStationUrl())->validate(
            'url',
            'https://example.com/stream',
            fn (string $attribute, ?string $message) => $this->fail("Validation failed for $attribute: $message"), // @phpstan-ignore-line
        );

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function acceptsUrlWhenServerRespondsWithError(): void
    {
        // Some streaming servers return errors but URL is still valid
        Http::fake([
            '*' => Http::response('', 502, ['Content-Type' => 'text/html']),
        ]);

        (new ValidRadioStationUrl())->validate(
            'url',
            'https://example.com/stream',
            fn (string $attribute, ?string $message) => $this->fail("Validation failed for $attribute: $message"), // @phpstan-ignore-line
        );

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function failsWhenConnectionFails(): void
    {
        // Only fail when there's a clear connection error
        Http::fake([
            '*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            },
        ]);

        (new ValidRadioStationUrl())->validate(
            'url',
            'https://example.com/stream',
            fn () => $this->addToAssertionCount(1),  // @phpstan-ignore-line
        );
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
