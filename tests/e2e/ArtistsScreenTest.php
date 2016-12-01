<?php

namespace E2E;

use Facebook\WebDriver\WebDriverBy;

class ArtistsScreenTest extends TestCase
{
    public function testArtistsScreen()
    {
        $this->loginAndGoTo('artists');

        static::assertNotEmpty($this->els('#artistsWrapper .artists article.item'));
        $firstArtist = $this->el('#artistsWrapper .artists article.item:nth-child(1)');
        static::assertNotEmpty($firstArtist->findElement(WebDriverBy::cssSelector('.info .name'))->getText());
        static::assertContains('5 albums • 50 songs', $firstArtist->findElement(WebDriverBy::cssSelector('.meta'))->getText());

        // test the view modes
        $this->click('#artistsWrapper > h1.heading > span.view-modes > a.list');
        static::assertCount(1, $this->els('#artistsWrapper > div.artists.as-list'));
        $this->click('#artistsWrapper > h1.heading > span.view-modes > a.thumbnails');
        static::assertCount(1, $this->els('#artistsWrapper > div.artists.as-thumbnails'));
    }
}
