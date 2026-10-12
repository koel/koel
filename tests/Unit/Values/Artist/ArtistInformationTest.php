<?php

namespace Tests\Unit\Values\Artist;

use App\Values\Artist\ArtistInformation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtistInformationTest extends TestCase
{
    #[Test]
    public function keepOnlySimpleFormattingInTheFetchedText(): void
    {
        $information = ArtistInformation::make(bio: [
            'summary' => '<p class="lead">A <i>thrash</i> band.<img src="logo.png"></p>',
            'full' => '<script>alert(1)</script>',
        ]);

        self::assertSame('<p>A <i>thrash</i> band.</p>', $information->bio['summary']);
        self::assertSame('', $information->bio['full']);
    }
}
