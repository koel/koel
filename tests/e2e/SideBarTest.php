<?php

namespace E2E;

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
        $this->see('#playlists > form.create');
        $this->typeIn('#playlists > form > input[type="text"]', 'Bar');
        $this->enter();
        $this->waitUntil(function () {
            $list = $this->els('#playlists .playlist');

            return end($list)->getText() === 'Bar';
        });

        // Double click to edit/rename a playlist
        $this->doubleClick('#playlists .playlist:nth-child(2)');
        $this->see('#playlists .playlist:nth-child(2) input[type="text"]');
        $this->typeIn('#playlists .playlist:nth-child(2) input[type="text"]', 'Qux');
        $this->enter();
        $this->seeText('Qux', '#playlists .playlist:nth-child(2)');

        // Edit with an empty name shouldn't do anything.
        $this->doubleClick('#playlists .playlist:nth-child(2)');
        $this->click('#playlists .playlist:nth-child(2) input[type="text"]')->clear();
        $this->enter();
        $this->seeText('Qux', '#playlists .playlist:nth-child(2)');
    }
}
