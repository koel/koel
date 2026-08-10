<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GravatarTest extends TestCase
{
    private const string EMAIL_SHA256 = 'efbe2fad818a477cc2eef45f6be5fd0a1111aead627c3529562f17f0375d4523';

    #[Test]
    public function identifiesTheEmailByItsSha256Hash(): void
    {
        self::assertSame(
            'https://www.gravatar.com/avatar/' . self::EMAIL_SHA256 . '?s=192&d=robohash',
            gravatar('koel@example.com'),
        );
    }

    #[Test]
    public function normalizesTheEmailBeforeHashing(): void
    {
        self::assertSame(gravatar('koel@example.com'), gravatar('  KOEL@Example.com  '));
    }

    #[Test]
    public function honorsTheConfiguredUrlAndDefault(): void
    {
        config([
            'services.gravatar.url' => 'https://gravatar.example.com/avatar',
            'services.gravatar.default' => 'identicon',
        ]);

        self::assertSame('https://gravatar.example.com/avatar/' . self::EMAIL_SHA256 . '?s=64&d=identicon', gravatar(
            'koel@example.com',
            64,
        ));
    }

    #[Test]
    public function encodesADefaultThatIsAnImageUrl(): void
    {
        config(['services.gravatar.default' => 'https://example.com/avatar.png']);

        self::assertSame(
            'https://www.gravatar.com/avatar/' . self::EMAIL_SHA256 . '?s=192&d=https%3A%2F%2Fexample.com%2Favatar.png',
            gravatar('koel@example.com'),
        );
    }
}
