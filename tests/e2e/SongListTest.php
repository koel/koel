<?php

namespace E2E;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;

class SongListTest extends TestCase
{
    use SongListActions;

    public function testSelection()
    {
        $this->loginAndWait()->repopulateList();

        // Single song selection
        static::assertContains('selected', $this->selectSong()->getAttribute('class'));

        // Shift+Click
        $this->selectRange();
        // should have 5 selected rows
        static::assertCount(5, $this->els('#queueWrapper tr.song-item.selected'));

        // Cmd+Click
        $this->cmdSelectSongs(2, 3);
        // should have only 3 selected rows remaining
        static::assertCount(3, $this->els('#queueWrapper tr.song-item.selected'));
        // 2nd and 3rd rows must not be selected
        static::assertNotContains(
            'selected',
            $this->el('#queueWrapper tr.song-item:nth-child(2)')->getAttribute('class')
        );
        static::assertNotContains(
            'selected',
            $this->el('#queueWrapper tr.song-item:nth-child(3)')->getAttribute('class')
        );

        // Delete key should remove selected songs
        $this->press(WebDriverKeys::DELETE);
        $this->waitUntil(function () {
            return count($this->els('#queueWrapper tr.song-item.selected')) === 0
            && count($this->els('#queueWrapper tr.song-item')) === 7;
        });

        // Ctrl+A/Cmd+A should select all songs
        $this->selectAllSongs();
        static::assertCount(7, $this->els('#queueWrapper tr.song-item.selected'));
    }

    public function testActionButtons()
    {
        $this->loginAndWait()->repopulateList();

        // Since no songs are selected, the "Shuffle All" button must be shown
        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated(
            WebDriverBy::cssSelector('#queueWrapper button.btn-shuffle-all')
        ));

        // Now we selected all songs for the "Shuffle Selected" button to be shown
        $this->selectAllSongs();
        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated(
            WebDriverBy::cssSelector('#queueWrapper button.btn-shuffle-selected')
        ));

        // Add to favorites
        $this->selectSong();
        $this->click('#queueWrapper .buttons button.btn-add-to');
        $this->click('#queueWrapper .buttons .add-to li.favorites');
        $this->goto('favorites');
        static::assertCount(1, $this->els('#favoritesWrapper tr.song-item'));

        $this->goto('queue');
        $this->selectSong();
        // Try adding a song into a new playlist
        $this->createPlaylist('Foo');
        $this->waitUntil(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('#playlists > ul'), 'Foo'
        ));
    }

    public function testSorting()
    {
        $this->loginAndWait()->repopulateList();

        // Confirm that we can't sort in Queue screen
        /** @var WebDriverElement $th */
        foreach ($this->els('#queueWrapper div.song-list-wrap th') as $th) {
            if (!$th->isDisplayed()) {
                continue;
            }

            foreach ($th->findElements(WebDriverBy::tagName('i')) as $sortDirectionIcon) {
                static::assertFalse($sortDirectionIcon->isDisplayed());
            }
        }

        // Now go to All Songs screen and sort there
        $this->goto('songs');
        $this->click('#songsWrapper div.song-list-wrap th:nth-child(2)');
        $last = null;
        $results = [];
        /** @var WebDriverElement $td */
        foreach ($this->els('#songsWrapper div.song-list-wrap td.title') as $td) {
            $current = $td->getText();
            $results[] = $last === null ? true : $current <= $last;
            $last = $current;
        }
        static::assertNotContains(false, $results);

        // Second click will reverse the sort
        $this->click('#songsWrapper div.song-list-wrap th:nth-child(2)');
        $last = null;
        $results = [];
        /** @var WebDriverElement $td */
        foreach ($this->els('#songsWrapper div.song-list-wrap td.title') as $td) {
            $current = $td->getText();
            $results[] = $last === null ? true : $current >= $last;
            $last = $current;
        }
        static::assertNotContains(false, $results);
    }

    public function testContextMenu()
    {
        $this->loginAndGoTo('songs');
        $this->rightClickOnSong();

        $by = WebDriverBy::cssSelector('#songsWrapper .song-menu');
        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated($by));

        // 7 sub menu items
        static::assertCount(7, $this->els('#songsWrapper .song-menu > li'));

        // Clicking the "Go to Album" menu item
        $this->click('#songsWrapper .song-menu > li:nth-child(2)');
        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated(
            WebDriverBy::cssSelector('#albumWrapper')
        ));

        // Clicking the "Go to Artist" menu item
        $this->back();
        $this->rightClickOnSong();
        $this->click('#songsWrapper .song-menu > li:nth-child(3)');
        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated(
            WebDriverBy::cssSelector('#artistWrapper')
        ));

        // Clicking "Edit"
        $this->back();
        $this->rightClickOnSong();
        $this->click('#songsWrapper .song-menu > li:nth-child(5)');
        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated(
            WebDriverBy::cssSelector('#editSongsOverlay form')
        ));
        // Updating song
        $this->typeIn('#editSongsOverlay form input[name="title"]', 'Foo');
        $this->typeIn('#editSongsOverlay form input[name="track"]', 99);
        $this->enter();
        $this->waitUntil(WebDriverExpectedCondition::invisibilityOfElementLocated(
            WebDriverBy::cssSelector('#editSongsOverlay form')
        ));
        static::assertEquals('99', $this->el('#songsWrapper tr.song-item:nth-child(1) .track-number')->getText());
        static::assertEquals('Foo', $this->el('#songsWrapper tr.song-item:nth-child(1) .title')->getText());
    }

    private function repopulateList()
    {
        // Go back to Albums and queue an album of 10 songs
        $this->goto('albums');
        $this->click('#albumsWrapper > div > article:nth-child(1) .meta a.shuffle-album');
        $this->goto('queue');
    }
}
