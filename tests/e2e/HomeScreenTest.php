<?php

namespace E2E;

use Facebook\WebDriver\Remote\RemoteWebElement;

class HomeScreenTest extends TestCase
{
    public function testHomeScreen()
    {
        $this->loginAndGoTo('home');

        // We must see some greetings
        static::assertTrue($this->el('#homeWrapper > h1')->isDisplayed());

        // 6 recently added albums
        static::assertCount(6, $this->els('#homeWrapper section.recently-added article'));

        // 10 recently added songs
        static::assertCount(10, $this->els('#homeWrapper .recently-added-song-list .song-item-home'));

        // Shuffle must work for latest albums
        $this->click('#homeWrapper section.recently-added article:nth-child(1) a.shuffle-album');
        static::assertCount(10, $this->els('#queueWrapper .song-list-wrap tr.song-item'));

        $this->goto('home');

        // Simulate a "double click to play" action
        /** @var $clickedSong RemoteWebElement */
        $clickedSong = $this->el('#homeWrapper section.recently-added > div > div:nth-child(2) li:nth-child(1) .details');
        $this->doubleClick($clickedSong);
        // The song must appear at the  top of "Recently played" section
        /** @var $mostRecentSong RemoteWebElement */
        $mostRecentSong = $this->el('#homeWrapper .recently-added-song-list .song-item-home:nth-child(1) .details');
        static::assertEquals($mostRecentSong->getText(), $clickedSong->getText());
    }
}
