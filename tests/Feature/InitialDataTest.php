<?php

namespace Tests\Feature;

use App\Models\PlaylistFolder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class InitialDataTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        $user = create_user();
        $parent = PlaylistFolder::factory()->for($user)->createOne();
        $child = PlaylistFolder::factory()->for($user)->for($parent, 'parent')->createOne();

        $response = $this->getAs('/api/data', $user)->assertJsonStructure([
            'settings',
            'playlists',
            'playlist_folders',
            'current_user',
            'uses_last_fm',
            'uses_you_tube',
            'uses_i_tunes',
            'uses_media_browser',
            'uses_ticketmaster',
            'allows_download',
            'allows_embedding',
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
            'supports_batch_downloading',
        ]);

        $folders = collect($response->json('playlist_folders'));

        self::assertNull($folders->firstWhere('id', $parent->id)['parent_id']);
        self::assertSame($parent->id, $folders->firstWhere('id', $child->id)['parent_id']);
    }
}
