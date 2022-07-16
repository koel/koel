<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use Carbon\Carbon;
use Illuminate\Cache\Repository as Cache;
use Illuminate\Support\Arr;
use LogicException;
use SpotifyWebAPI\Session as SpotifySession;
use SpotifyWebAPI\SpotifyWebAPI;

class SpotifyService
{
    private ?SpotifySession $session;

    public function __construct(private SpotifyWebAPI $client, private Cache $cache)
    {
        if (static::enabled()) {
            $this->session = new SpotifySession(config('koel.spotify.client_id'), config('koel.spotify.client_secret'));
            $this->client->setOptions(['return_assoc' => true]);
            $this->client->setAccessToken($this->getAccessToken());
        }
    }

    public static function enabled(): bool
    {
        return config('koel.spotify.client_id') && config('koel.spotify.client_secret');
    }

    private function getAccessToken(): string
    {
        if (!$this->session) {
            throw new LogicException();
        }

        if (!$this->cache->has('spotify.access_token')) {
            $this->session->requestCredentialsToken();
            $token = $this->session->getAccessToken();
            $this->cache->put('spotify.access_token', $token, Carbon::now()->addMinutes(59));
        }

        return $this->cache->get('spotify.access_token');
    }

    public function tryGetArtistImage(Artist $artist): ?string
    {
        if (!static::enabled()) {
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

        if ($album->name === Album::UNKNOWN_NAME) {
            return null;
        }

        return Arr::get(
            $this->client->search("{$album->artist->name} {$album->name}", 'album', ['limit' => 1]),
            'albums.items.0.images.0.url'
        );
    }
}
