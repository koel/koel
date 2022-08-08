<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\ApiClients\SpotifyClient;
use Illuminate\Support\Arr;

class SpotifyService
{
    public function __construct(private SpotifyClient $client)
    {
    }

    public static function enabled(): bool
    {
        return config('koel.spotify.client_id') && config('koel.spotify.client_secret');
    }

    public function tryGetArtistImage(Artist $artist): ?string
    {
        if (!static::enabled()) {
            return null;
        }

        if ($artist->is_various || $artist->is_unknown) {
            return null;
        }

        return Arr::get(
            $this->client->search($artist->name, 'artist', ['limit' => 1]),
            'artists.items.0.images.0.url'
        );
    }

    public function tryGetAlbumCover(Album $album): ?string
    {
        if (!static::enabled()) {
            return null;
        }

        if ($album->is_unknown || $album->artist->is_unknown || $album->artist->is_various) {
            return null;
        }

        return Arr::get(
            $this->client->search("{$album->name} artist:{$album->artist->name}", 'album', ['limit' => 1]),
            'albums.items.0.images.0.url'
        );
    }
}
