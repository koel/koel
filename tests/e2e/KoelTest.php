<?php

namespace E2E;

use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

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

        $this->_testSideBar();
        $this->_testHomeScreen();

        // While we're at this, test logging out as well.
        $this->click('#userBadge > a.logout');
        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated(
           WebDriverBy::cssSelector($formSelector)
        ));
    }

    private function _testSideBar()
    {
        // All basic navigation
        foreach (['home', 'queue', 'songs', 'albums', 'artists', 'youtube', 'settings', 'users'] as $screen) {
            $this->goto($screen);
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
