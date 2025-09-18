<?php

namespace Tests\Unit\KoelPlus\Values;

use App\Values\EmbedOptions;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class EmbedOptionsTest extends PlusTestCase
{
    #[Test]
    public function themeAndPreviewCanBeCustomizedForCommunityLicense(): void
    {
        $encrypted = (string) EmbedOptions::make('cat', 'compact', true);
        $options = EmbedOptions::fromEncrypted($encrypted);

        self::assertSame('cat', $options->theme);
        self::assertSame('compact', $options->layout);
        self::assertTrue($options->preview);
    }
}
