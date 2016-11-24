<?php

namespace E2E;

class SideBarTest extends TestCase
{
    public function testSideBar()
    {
        $this->loginAndWait();

        // All basic navigation
        foreach (['home', 'queue', 'songs', 'albums', 'artists', 'youtube', 'settings', 'users'] as $screen) {
            $this->goto($screen)
                ->waitUntil(function () use ($screen) {
                    return $this->driver->getCurrentURL() === $this->url.'/#!/'.$screen;
                });
        }

        // Add a playlist
        $this->click('#playlists > h1 > i.create');
        $this->see('#playlists > form.create')
            ->typeIn('#playlists > form > input[type="text"]', 'Bar')
            ->enter()
            ->waitUntil(function () {
                $list = $this->els('#playlists .playlist');

                return end($list)->getText() === 'Bar';
            });

        // Double click to edit/rename a playlist
        $this->doubleClick('#playlists .playlist:nth-child(2)')
            ->see('#playlists .playlist:nth-child(2) input[type="text"]')
            ->typeIn('#playlists .playlist:nth-child(2) input[type="text"]', 'Qux')
            ->enter()
            ->seeText('Qux', '#playlists .playlist:nth-child(2)');

        // Edit with an empty name shouldn't do anything.
        $this->doubleClick('#playlists .playlist:nth-child(2)');
        $this->click('#playlists .playlist:nth-child(2) input[type="text"]')->clear();
        $this->enter()->seeText('Qux', '#playlists .playlist:nth-child(2)');
    }
}
