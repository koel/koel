<?php

namespace E2E;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class PlaylistScreenTest extends TestCase
{
    use SongListActions;

    public function testPlaylistScreen()
    {
        $this->loginAndWait()
            ->goto('songs')
            ->selectRange()
            ->createPlaylist('Bar');

        $this->waitUntil(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('#playlists > ul'), 'Bar'
        ));

        $this->click('#sidebar .playlist:nth-last-child(1)');
        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated(
            WebDriverBy::id('playlistWrapper')
        ));

        $this->click('#playlistWrapper .btn-delete-playlist');
        // expect a confirmation
        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated(
            WebDriverBy::cssSelector('.sweet-alert')
        ));
    }
}
