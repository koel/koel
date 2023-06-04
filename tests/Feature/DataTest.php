<?php

namespace Tests\Feature;

class DataTest extends TestCase
{
    public function testIndex(): void
    {
        $this->getAs('/api/data')->assertJsonStructure([
            'settings',
            'playlists',
            'playlist_folders',
            'current_user',
            'use_last_fm',
            'use_you_tube',
            'use_i_tunes',
            'allow_download',
            'supports_transcoding',
            'cdn_url',
            'current_version',
            'latest_version',
            'song_count',
            'song_length',
        ]);
    }
}
