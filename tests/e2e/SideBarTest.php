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
            /** @var WebDriverElement $mostRecentPlaylist */
            $list = $this->els('#playlists .playlist');
            $mostRecentPlaylist = end($list);

            return $mostRecentPlaylist->getText() === 'Bar';
        });

        // Double click to edit/rename a playlist
        $this->doubleClick($mostRecentPlaylist);
        $this->waitUntil(function () use (&$mostRecentPlaylist) {
            return count($mostRecentPlaylist->findElements(WebDriverBy::cssSelector('input[type="text"]')));
        });
        $this->typeIn(
            $mostRecentPlaylist->findElement(WebDriverBy::cssSelector('input[type="text"]')),
            'Baz'
        );
        $this->enter();
        $this->waitUntil(function () {
            $list = $this->els('#playlists .playlist');
            $mostRecentPlaylist = end($list);

            return $mostRecentPlaylist->getText() === 'Baz';
        });

        // Edit with an empty name shouldn't do anything.
        $this->doubleClick($mostRecentPlaylist);
        $mostRecentPlaylist->findElement(WebDriverBy::cssSelector('input[type="text"]'))->clear();
        $this->enter();
        $this->waitUntil(function () {
            $list = $this->els('#playlists .playlist');
            $mostRecentPlaylist = end($list);

            return $mostRecentPlaylist->getText() === 'Baz';
        });
    }
}
