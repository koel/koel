<?php

namespace Tests\Unit\Helpers;

use Illuminate\Http\Request;
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
        url()->setRequest(Request::create('http://music.example.com/'));

        self::assertSame('http://music.example.com/', base_url());
    }
}
