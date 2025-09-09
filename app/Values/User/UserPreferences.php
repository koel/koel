<?php

namespace App\Values\User;

use App\Values\Equalizer;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Webmozart\Assert\Assert;

final class UserPreferences implements Arrayable, JsonSerializable
{
    private const CASTS = [
        'albums_favorites_only' => 'boolean',
        'artists_favorites_only' => 'boolean',
        'confirm_before_closing' => 'boolean',
        'continuous_playback' => 'boolean',
        'include_public_media' => 'boolean',
        'lyrics_zoom_level' => 'integer',
        'make_uploads_public' => 'boolean',
        'podcasts_favorites_only' => 'boolean',
        'radio_stations_favorites_only' => 'boolean',
        'show_album_art_overlay' => 'boolean',
        'show_now_playing_notification' => 'boolean',
        'support_bar_no_bugging' => 'boolean',
        'transcode_on_mobile' => 'boolean',
        'transcode_quality' => 'integer',
        'volume' => 'float',
    ];

    private const CUSTOMIZABLE_KEYS = [
        'active_extra_panel_tab',
        'albums_favorites_only',
        'albums_sort_field',
        'albums_sort_order',
        'albums_view_mode',
        'artists_favorites_only',
        'artists_sort_field',
        'artists_sort_order',
        'artists_view_mode',
        'confirm_before_closing',
        'continuous_playback',
        'equalizer',
        'genres_sort_field',
        'genres_sort_order',
        'include_public_media',
        'lyrics_zoom_level',
        'make_uploads_public',
        'podcasts_favorites_only',
        'podcasts_sort_field',
        'podcasts_sort_order',
        'radio_stations_favorites_only',
        'radio_stations_sort_field',
        'radio_stations_sort_order',
        'radio_stations_view_mode',
        'repeat_mode',
        'show_album_art_overlay',
        'show_now_playing_notification',
        'support_bar_no_bugging',
        'theme',
        'transcode_on_mobile',
        'transcode_quality',
        'visualizer',
        'volume',
    ];

    private const ALL_KEYS = self::CUSTOMIZABLE_KEYS + ['lastfm_session_key'];

    private function __construct(
        public float $volume,
        public string $repeatMode,
        public Equalizer $equalizer,
        public string $albumsViewMode,
        public string $artistsViewMode,
        public string $radioStationsViewMode,
        public string $albumsSortField,
        public string $artistsSortField,
        public string $genresSortField,
        public string $podcastsSortField,
        public string $radioStationsSortField,
        public string $albumsSortOrder,
        public string $artistsSortOrder,
        public string $genresSortOrder,
        public string $podcastsSortOrder,
        public string $radioStationsSortOrder,
        public bool $albumsFavoritesOnly,
        public bool $artistsFavoritesOnly,
        public bool $podcastsFavoritesOnly,
        public bool $radioStationsFavoritesOnly,
        public string $theme,
        public bool $showNowPlayingNotification,
        public bool $confirmBeforeClosing,
        public bool $transcodeOnMobile,
        public int $transcodeQuality,
        public bool $showAlbumArtOverlay,
        public bool $makeUploadsPublic,
        public bool $includePublicMedia,
        public bool $supportBarNoBugging,
        public bool $continuousPlayback,
        public int $lyricsZoomLevel,
        public string $visualizer,
        public ?string $activeExtraPanelTab,
        public ?string $lastFmSessionKey
    ) {
        Assert::oneOf($this->repeatMode, ['NO_REPEAT', 'REPEAT_ALL', 'REPEAT_ONE']);
        Assert::oneOf($this->artistsViewMode, ['list', 'thumbnails']);
        Assert::oneOf($this->albumsViewMode, ['list', 'thumbnails']);
        Assert::oneOf($this->radioStationsViewMode, ['list', 'thumbnails']);
        Assert::oneOf($this->activeExtraPanelTab, [null, 'Lyrics', 'Artist', 'Album', 'YouTube']);
        Assert::oneOf(strtolower($this->albumsSortOrder), ['asc', 'desc']);
        Assert::oneOf($this->albumsSortField, ['name', 'artist_name', 'year', 'created_at']);
        Assert::oneOf(strtolower($this->artistsSortOrder), ['asc', 'desc']);
        Assert::oneOf($this->artistsSortField, ['name', 'created_at']);
        Assert::oneOf($this->genresSortField, ['name', 'song_count']);
        Assert::oneOf(strtolower($this->genresSortOrder), ['asc', 'desc']);
        Assert::oneOf($this->podcastsSortField, ['title', 'last_played_at', 'subscribed_at', 'author']);
        Assert::oneOf(strtolower($this->podcastsSortOrder), ['asc', 'desc']);
        Assert::oneOf($this->radioStationsSortField, ['name', 'created_at']);
        Assert::oneOf(strtolower($this->radioStationsSortOrder), ['asc', 'desc']);

        if (!in_array($this->transcodeQuality, [64, 96, 128, 192, 256, 320], true)) {
            $this->transcodeQuality = 128;
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            volume: $data['volume'] ?? 7.0,
            repeatMode: $data['repeat_mode'] ?? 'NO_REPEAT',
            equalizer: isset($data['equalizer']) ? Equalizer::tryMake($data['equalizer']) : Equalizer::default(),
            albumsViewMode: $data['albums_view_mode'] ?? 'thumbnails',
            artistsViewMode: $data['artists_view_mode'] ?? 'thumbnails',
            radioStationsViewMode: $data['radio_stations_view_mode'] ?? 'thumbnails',
            albumsSortField: $data['albums_sort_field'] ?? 'name',
            artistsSortField: $data['artists_sort_field'] ?? 'name',
            genresSortField: $data['genres_sort_field'] ?? 'name',
            podcastsSortField: $data['podcasts_sort_field'] ?? 'title',
            radioStationsSortField: $data['radio_stations_sort_field'] ?? 'name',
            albumsSortOrder: $data['albums_sort_order'] ?? 'asc',
            artistsSortOrder: $data['artists_sort_order'] ?? 'asc',
            genresSortOrder: $data['genres_sort_order'] ?? 'asc',
            podcastsSortOrder: $data['podcasts_sort_order'] ?? 'asc',
            radioStationsSortOrder: $data['radio_stations_sort_order'] ?? 'asc',
            albumsFavoritesOnly: $data['albums_favorites_only'] ?? false,
            artistsFavoritesOnly: $data['artists_favorites_only'] ?? false,
            podcastsFavoritesOnly: $data['podcasts_favorites_only'] ?? false,
            radioStationsFavoritesOnly: $data['radio_stations_favorites_only'] ?? false,
            theme: $data['theme'] ?? 'classic',
            showNowPlayingNotification: $data['show_now_playing_notification'] ?? true,
            confirmBeforeClosing: $data['confirm_before_closing'] ?? false,
            transcodeOnMobile: $data['transcode_on_mobile'] ?? true,
            transcodeQuality: $data['transcode_quality'] ?? config('koel.streaming.bitrate'),
            showAlbumArtOverlay: $data['show_album_art_overlay'] ?? true,
            makeUploadsPublic: $data['make_uploads_public'] ?? false,
            includePublicMedia: $data['include_public_media'] ?? true,
            supportBarNoBugging: $data['support_bar_no_bugging'] ?? false,
            continuousPlayback: $data['continuous_playback'] ?? false,
            lyricsZoomLevel: $data['lyrics_zoom_level'] ?? 1,
            visualizer: $data['visualizer'] ?? 'default',
            activeExtraPanelTab: $data['active_extra_panel_tab'] ?? null,
            lastFmSessionKey: $data['lastfm_session_key'] ?? null,
        );
    }

    public static function customizable(string $key): bool
    {
        return in_array($key, self::CUSTOMIZABLE_KEYS, true);
    }

    public function set(string $key, mixed $value): self
    {
        self::assertValidKey($key);

        $cast = self::CASTS[$key] ?? null;

        $value = match ($cast) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            default => $value,
        };

        $arr = $this->toArray();
        $arr[$key] = $value;

        return self::fromArray($arr);
    }

    public static function assertValidKey(string $key): void
    {
        Assert::inArray($key, self::ALL_KEYS);
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'theme' => $this->theme,
            'show_now_playing_notification' => $this->showNowPlayingNotification,
            'confirm_before_closing' => $this->confirmBeforeClosing,
            'show_album_art_overlay' => $this->showAlbumArtOverlay,
            'transcode_on_mobile' => $this->transcodeOnMobile,
            'transcode_quality' => $this->transcodeQuality,
            'make_uploads_public' => $this->makeUploadsPublic,
            'include_public_media' => $this->includePublicMedia,
            'lastfm_session_key' => $this->lastFmSessionKey,
            'support_bar_no_bugging' => $this->supportBarNoBugging,
            'albums_view_mode' => $this->albumsViewMode,
            'artists_view_mode' => $this->artistsViewMode,
            'radio_stations_view_mode' => $this->radioStationsViewMode,
            'albums_sort_field' => $this->albumsSortField,
            'artists_sort_field' => $this->artistsSortField,
            'genres_sort_field' => $this->genresSortField,
            'podcasts_sort_field' => $this->podcastsSortField,
            'radio_stations_sort_field' => $this->radioStationsSortField,
            'albums_sort_order' => $this->albumsSortOrder,
            'artists_sort_order' => $this->artistsSortOrder,
            'genres_sort_order' => $this->genresSortOrder,
            'podcasts_sort_order' => $this->podcastsSortOrder,
            'radio_stations_sort_order' => $this->radioStationsSortOrder,
            'albums_favorites_only' => $this->albumsFavoritesOnly,
            'artists_favorites_only' => $this->artistsFavoritesOnly,
            'podcasts_favorites_only' => $this->podcastsFavoritesOnly,
            'radio_stations_favorites_only' => $this->radioStationsFavoritesOnly,
            'repeat_mode' => $this->repeatMode,
            'volume' => $this->volume,
            'equalizer' => $this->equalizer->toArray(),
            'lyrics_zoom_level' => $this->lyricsZoomLevel,
            'visualizer' => $this->visualizer,
            'active_extra_panel_tab' => $this->activeExtraPanelTab,
            'continuous_playback' => $this->continuousPlayback,
        ];
    }

    /** @inheritdoc */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
