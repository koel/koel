<?php

namespace E2E;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;

class SideBarTest extends TestCase
{
    public function testSideBar()
    {
        $this->loginAndWait();

        // All basic navigation
        foreach (['home', 'queue', 'songs', 'albums', 'artists', 'youtube', 'settings', 'users'] as $screen) {
            $this->goto($screen);
            $this->waitUntil(function () use ($screen) {
                return $this->driver->getCurrentURL() === $this->url.'/#!/'.$screen;
            });
        }

        // Add a playlist
        $this->click('#playlists > h1 > i.create');
        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated(
            WebDriverBy::cssSelector('#playlists > form.create')
        ));
        $this->typeIn('#playlists > form > input[type="text"]', 'Bar');
        $this->enter();
        /** @var WebDriverElement $mostRecentPlaylist */
        $mostRecentPlaylist = null;
        $this->waitUntil(function () use (&$mostRecentPlaylist) {
            /* @var WebDriverElement $mostRecentPlaylist */
            $list = $this->els('#playlists .playlist');
            $mostRecentPlaylist = end($list);

            return $mostRecentPlaylist->getText() === 'Bar';
        });

        // Double click to edit/rename a playlist
        $this->doubleClick('#playlists .playlist:nth-child(2)');
        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated(
            WebDriverBy::cssSelector('#playlists .playlist:nth-child(2) input[type="text"]')
        ));
        $this->typeIn('#playlists .playlist:nth-child(2) input[type="text"]', 'Qux');
        $this->enter();
        $this->waitUntil(
            WebDriverExpectedCondition::textToBePresentInElement(
                WebDriverBy::cssSelector('#playlists .playlist:nth-child(2)'),
                'Qux'
            )
        );

        // Edit with an empty name shouldn't do anything.
        $this->doubleClick('#playlists .playlist:nth-child(2)');
        $this->click('#playlists .playlist:nth-child(2) input[type="text"]')->clear();
        $this->enter();
        $this->waitUntil(
            WebDriverExpectedCondition::textToBePresentInElement(
                WebDriverBy::cssSelector('#playlists .playlist:nth-child(2)'),
                'Qux'
            )
        );
    }
}
