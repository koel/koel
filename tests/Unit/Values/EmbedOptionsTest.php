<?php

namespace Tests\Unit\Values;

use App\Values\EmbedOptions;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmbedOptionsTest extends TestCase
{
    #[Test]
    public function makeDefault(): void
    {
        $encrypted = (string) EmbedOptions::make();
        $options = EmbedOptions::fromEncrypted($encrypted);

        self::assertSame('classic', $options->theme);
        self::assertSame('full', $options->layout);
        self::assertFalse($options->preview);
    }

    #[Test]
    public function make(): void
    {
        $encrypted = (string) EmbedOptions::make(layout: 'compact');
        $options = EmbedOptions::fromEncrypted($encrypted);

        self::assertSame('classic', $options->theme);
        self::assertSame('compact', $options->layout);
        self::assertFalse($options->preview);
    }

    #[Test]
    public function themeAndPreviewCannotBeCustomizedForCommunityLicense(): void
    {
        $encrypted = (string) EmbedOptions::make('cat', 'compact', true);
        $options = EmbedOptions::fromEncrypted($encrypted);

        self::assertSame('classic', $options->theme);
        self::assertSame('compact', $options->layout);
        self::assertFalse($options->preview);
    }

    #[Test]
    public function invalidEncryptedResolvesIntoDefault(): void
    {
        $options = EmbedOptions::fromEncrypted('foo');

        self::assertSame('classic', $options->theme);
        self::assertSame('full', $options->layout);
        self::assertFalse($options->preview);
    }

    #[Test]
    public function fromRequest(): void
    {
        $encrypted = (string) EmbedOptions::make(layout: 'compact');
        $request = Mockery::mock(Request::class, ['route' => $encrypted]);

        $options = EmbedOptions::fromRequest($request);

        self::assertSame('classic', $options->theme);
        self::assertSame('compact', $options->layout);
        self::assertFalse($options->preview);
    }
}
