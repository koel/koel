<?php

namespace Tests\Unit\Rules;

use App\Rules\HasAudioContentType;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HasAudioContentTypeTest extends TestCase
{
    #[Test]
    public function valid(): void
    {
        Http::fake([
            '*' => Http::response('', 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        (new HasAudioContentType())->validate(
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
    public function invalidContentType(?string $contentType): void
    {
        Http::fake([
            '*' => Http::response('', 200, ['Content-Type' => $contentType]),
        ]);

        (new HasAudioContentType())->validate(
            'url',
            'https://example.com/stream',
            fn () => $this->addToAssertionCount(1), // @phpstan-ignore-line
        );
    }
}
