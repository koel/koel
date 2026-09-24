<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BaseUrlTest extends TestCase
{
    #[Test]
    public function pointAtTheApplicationEvenWhenAssetsAreServedFromElsewhere(): void
    {
        url()->useAssetOrigin('https://cdn.example.com');

        self::assertSame('https://cdn.example.com/', asset(''));
        self::assertSame('http://localhost/', base_url());
    }

    #[Test]
    public function followTheHostTheRequestCameIn(): void
    {
        $this->get('http://music.example.com/')->assertSee('"base_url":"http:\/\/music.example.com\/"', escape: false);
    }
}
