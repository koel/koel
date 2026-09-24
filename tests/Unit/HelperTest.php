<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HelperTest extends TestCase
{
    #[Test]
    public function baseUrlPointsAtTheApplicationEvenWhenAssetsAreServedFromElsewhere(): void
    {
        url()->useAssetOrigin('https://cdn.example.com');

        self::assertSame('https://cdn.example.com/', asset(''));
        self::assertSame('http://localhost/', base_url());
    }

    #[Test]
    public function baseUrlFollowsTheHostTheRequestCameIn(): void
    {
        url()->setRequest(Request::create('http://music.example.com/'));

        self::assertSame('http://music.example.com/', base_url());
    }

    #[Test]
    public function appUrlIgnoresTheHostTheRequestCameIn(): void
    {
        config(['app.url' => 'https://music.example.com/']);
        url()->setRequest(Request::create('http://evil.example/'));

        self::assertSame('https://music.example.com/#/reset-password/payload', app_url('/#/reset-password/payload'));
        self::assertSame('https://music.example.com/', app_url());
    }
}
