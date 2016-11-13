<?php

namespace E2E;

use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;

class KoelTest extends TestCase
{
    public function testDefaults()
    {
        static::assertContains('Koel', $this->driver->getTitle());

        $formSelector = '#app > div.login-wrapper > form';

        // Our login form should be there.
        static::assertCount(1, $this->els($formSelector));

        // We submit rubbish and expect an error class on the form.
        $this->login('foo@bar.com', 'ThisIsWongOnSoManyLevels');
        $this->waitUntil(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::cssSelector("$formSelector.error")
        ));

        // Now we submit good stuff and make sure we're in.
        $this->login();
        $this->waitUntil(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('#userBadge > a.view-profile.control > span'), 'Koel Admin'
        ));

        // Default URL must be Home
        static::assertEquals($this->url.'/#!/home', $this->driver->getCurrentURL());

        // $this->_testSideBar();
        // $this->_testHomeScreen();
        return $this->_testQueueScreen();

        // While we're at this, test logging out as well.
        $this->click('#userBadge > a.logout');
        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated(
           WebDriverBy::cssSelector($formSelector)
        ));
    }

    private function _testSideBar()
    {
        // All basic navigation
        foreach(['home', 'queue', 'songs', 'albums', 'artists', 'youtube', 'settings', 'users'] as $screen) {
            $this->goTo($screen);
            $this->waitUntil(function () use ($screen) {
                return $this->driver->getCurrentURL() === $this->url.'/#!/'.$screen;
            });
        }
    }

    private function _testHomeScreen()
    {
        $this->click('#sidebar a.home');

        // We must see some greetings
        static::assertTrue($this->el('#homeWrapper > h1')->isDisplayed());

        // 6 recently added albums
        static::assertCount(6, $this->els('#homeWrapper section.recently-added article'));

        // 10 recently added songs
        static::assertCount(10, $this->els('#homeWrapper .recently-added-song-list .song-item-home'));

        // Shuffle must work for latest albums
        $this->click('#homeWrapper section.recently-added article:nth-child(1) span.right a:nth-child(1)');
        static::assertCount(10, $this->els('#queueWrapper .song-list-wrap tr.song-item'));

        $this->goTo('home');

        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated(
            WebDriverBy::cssSelector('#homeWrapper section.recently-added')
        ));
        // Simulate a "double click to play" action
        /** @var $clickedSong RemoteWebElement */
        $clickedSong = $this->el('#homeWrapper section.recently-added > div > div:nth-child(2) li:nth-child(1) .details');
        $this->doubleClick($clickedSong);
        // The song must appear at the  top of "Recently played" section
        /** @var $mostRecentSong RemoteWebElement */
        $mostRecentSong = $this->el('#homeWrapper .recently-added-song-list .song-item-home:nth-child(1) .details');
        static::assertEquals($mostRecentSong->getText(), $clickedSong->getText());
    }

    private function _testQueueScreen()
    {
        $this->goTo('queue');
        static::assertContains('Current Queue', $this->el('#queueWrapper > h1 > span')->getText());

        // Clear the queue
        $this->clearQueue();
        static::assertEmpty($this->els('#queueWrapper tr.song-item'));

        // Go back to Albums and queue an album of 10 songs
        $this->goTo('albums');
        $this->click('#albumsWrapper > div > article:nth-child(1) > footer > p > span.right > a:nth-child(1)');

        $this->goTo('queue');
        // Single song selection
        static::assertContains(
            'selected',
            $this->click('#queueWrapper tr.song-item:nth-child(1)')->getAttribute('class')
        );
        // shift+click
        (new WebDriverActions($this->driver))
            ->keyDown(null, WebDriverKeys::SHIFT)
            ->click($this->el('#queueWrapper tr.song-item:nth-child(5)'))
            ->keyUp(null, WebDriverKeys::SHIFT)
            ->perform();
        // should have 5 selected rows
        static::assertCount(5, $this->els('#queueWrapper tr.song-item.selected'));
        // Cmd+Click
        (new WebDriverActions($this->driver))
            ->keyDown(null, WebDriverKeys::COMMAND)
            ->click($this->el('#queueWrapper tr.song-item:nth-child(2)'))
            ->click($this->el('#queueWrapper tr.song-item:nth-child(3)'))
            ->keyUp(null, WebDriverKeys::COMMAND)
            ->perform();
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

        // Ctrl+A/Cmd+A should select all remaining songs
        (new WebDriverActions($this->driver))
            ->keyDown(null, WebDriverKeys::COMMAND)
            ->keyDown(null, 'A')
            ->keyUp(null, 'A')
            ->keyUp(null, WebDriverKeys::COMMAND)
            ->perform();
        static::assertCount(7, $this->els('#queueWrapper tr.song-item.selected'));
        // Try adding these songs into a new playlist
        $this->click('#queueWrapper .buttons button.btn.btn-green');
        $this->typeIn('#queueWrapper .buttons input[type="text"]', 'Foo');
        $this->enter();
        $this->waitUntil(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('#playlists > ul'), 'Foo'
        ));

        // Now go back to the queue and try adding a song into favorites
        $this->goTo('queue');
        /** @var WebDriverElement $firstSongInQueue */
        $firstSongInQueue = $this->el('#queueWrapper tr.song-item:nth-child(1) .title')->click();
        // TODO: There's a bug here.
        // After deleting, selection goes wrong (clicking first row selects second).
        var_dump($firstSongInQueue->getText());
        $this->click('#queueWrapper .buttons button.btn.btn-green');
        $this->click('#queueWrapper .buttons li:nth-child(1)');
        $this->goTo('favorites');
        var_dump($this->el('#favoritesWrapper tr.song-item:nth-last-child(1) .title')->getText());
        static::assertEquals($firstSongInQueue->getText(),
            $this->el('#favoritesWrapper tr.song-item:nth-last-child(1) .title')->getText());
    }
}
