<?php

namespace E2E;

use Facebook\WebDriver\WebDriverBy;

class AlbumsScreenTest extends TestCase
{
    public function testAlbumsScreen()
    {
        $this->loginAndGoTo('albums');

        static::assertNotEmpty($this->els('#albumsWrapper .albums article.item'));
        $firstAlbum = $this->el('#albumsWrapper .albums article.item:nth-child(1)');
        static::assertNotEmpty($firstAlbum->findElement(WebDriverBy::cssSelector('.info .name'))->getText());
        static::assertNotEmpty($firstAlbum->findElement(WebDriverBy::cssSelector('.info .artist'))->getText());
        static::assertContains('10 songs', $firstAlbum->findElement(WebDriverBy::cssSelector('.meta'))->getText());

        // test the view modes
        $this->click('#albumsWrapper > h1.heading > span.view-modes > a.list');
        static::assertCount(1, $this->els('#albumsWrapper > div.albums.as-list'));
        $this->click('#albumsWrapper > h1.heading > span.view-modes > a.thumbnails');
        static::assertCount(1, $this->els('#albumsWrapper > div.albums.as-thumbnails'));
    }
}
