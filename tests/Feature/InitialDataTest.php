<?php

namespace Tests\Feature;

use Tests\TestCase;

class InitialDataTest extends TestCase
{
    public function testIndex(): void
    {
        $this->getAs('/api/data')->assertJsonStructure([
            'settings',
            'playlists',
            'playlist_folders',
            'current_user',
            'uses_last_fm',
            'uses_you_tube',
            'uses_i_tunes',
            'allows_download',
            'supports_transcoding',
            'cdn_url',
            'current_version',
            'latest_version',
            'song_count',
            'song_length',
            'queue_state' => [
                'songs',
                'current_song',
                'playback_position',
            ],
            'koel_plus' => [
                'active',
                'short_key',
                'customer_name',
                'customer_email',
                'product_id',
            ],
        ]);
    }
}
