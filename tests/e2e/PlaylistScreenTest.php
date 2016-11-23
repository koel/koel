<?php

namespace E2E;

class PlaylistScreenTest extends TestCase
{
    use SongListActions;

    public function testPlaylistScreen()
    {
        $this->loginAndGoTo('songs')
            ->selectRange()
            ->createPlaylist('Bar');

        $this->waitUntilTextSeenIn('Bar', '#playlists > ul');

        $this->click('#sidebar .playlist:nth-last-child(1)');
        $this->waitUntilSeen('#playlistWrapper');

        $this->click('#playlistWrapper .btn-delete-playlist');
        // expect a confirmation
        $this->waitUntilSeen('.sweet-alert');
    }
}
