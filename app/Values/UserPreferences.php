<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Webmozart\Assert\Assert;

final class UserPreferences implements Arrayable, JsonSerializable
{
    private const CASTS = [
        'volume' => 'float',
        'show_now_playing_notification' => 'boolean',
        'confirm_before_closing' => 'boolean',
        'transcode_on_mobile' => 'boolean',
        'transcode_quality' => 'integer',
        'show_album_art_overlay' => 'boolean',
        'lyrics_zoom_level' => 'integer',
        'make_uploads_public' => 'boolean',
        'continuous_playback' => 'boolean',
    ];

    private const CUSTOMIZABLE_KEYS = [
        'volume',
        'repeat_mode',
        'equalizer',
        'artists_view_mode',
        'albums_view_mode',
        'albums_sort_field',
        'albums_sort_order',
        'theme',
        'show_now_playing_notification',
        'confirm_before_closing',
        'transcode_on_mobile',
        'transcode_quality',
        'show_album_art_overlay',
        'make_uploads_public',
        'support_bar_no_bugging',
        'lyrics_zoom_level',
        'visualizer',
        'active_extra_panel_tab',
        'continuous_playback',
    ];

    private const ALL_KEYS = self::CUSTOMIZABLE_KEYS + ['lastfm_session_key'];

    private function __construct(
        public float $volume,
        public string $repeatMode,
        public Equalizer $equalizer,
        public string $artistsViewMode,
        public string $albumsViewMode,
        public string $albumsSortField,
        public string $albumsSortOrder,
        public string $theme,
        public bool $showNowPlayingNotification,
        public bool $confirmBeforeClosing,
        public bool $transcodeOnMobile,
        public int $transcodeQuality,
        public bool $showAlbumArtOverlay,
        public bool $makeUploadsPublic,
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
        Assert::oneOf($this->activeExtraPanelTab, [null, 'Lyrics', 'Artist', 'Album', 'YouTube']);
        Assert::oneOf(strtolower($this->albumsSortOrder), ['asc', 'desc']);
        Assert::oneOf($this->albumsSortField, ['name', 'artist_name', 'year']);

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
            artistsViewMode: $data['artists_view_mode'] ?? 'thumbnails',
            albumsViewMode: $data['albums_view_mode'] ?? 'thumbnails',
            albumsSortField: $data['albums_sort_field'] ?? 'name',
            albumsSortOrder: $data['albums_sort_order'] ?? 'asc',
            theme: $data['theme'] ?? 'classic',
            showNowPlayingNotification: $data['show_now_playing_notification'] ?? true,
            confirmBeforeClosing: $data['confirm_before_closing'] ?? false,
            transcodeOnMobile: $data['transcode_on_mobile'] ?? true,
            transcodeQuality: $data['transcode_quality'] ?? config('koel.streaming.bitrate'),
            showAlbumArtOverlay: $data['show_album_art_overlay'] ?? true,
            makeUploadsPublic: $data['make_uploads_public'] ?? false,
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

    /** @return array<mixed> */
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
            'lastfm_session_key' => $this->lastFmSessionKey,
            'support_bar_no_bugging' => $this->supportBarNoBugging,
            'artists_view_mode' => $this->artistsViewMode,
            'albums_view_mode' => $this->albumsViewMode,
            'albums_sort_field' => $this->albumsSortField,
            'albums_sort_order' => $this->albumsSortOrder,
            'repeat_mode' => $this->repeatMode,
            'volume' => $this->volume,
            'equalizer' => $this->equalizer->toArray(),
            'lyrics_zoom_level' => $this->lyricsZoomLevel,
            'visualizer' => $this->visualizer,
            'active_extra_panel_tab' => $this->activeExtraPanelTab,
            'continuous_playback' => $this->continuousPlayback,
        ];
    }

    /** @return array<mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
