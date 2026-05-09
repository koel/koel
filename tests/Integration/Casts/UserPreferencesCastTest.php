<?php

namespace Tests\Integration\Casts;

use App\Values\User\UserPreferences;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class UserPreferencesCastTest extends TestCase
{
    #[Test]
    public function cast(): void
    {
        $user = create_user([
            'preferences' => [
                'lastfm_session_key' => 'foo',
            ],
        ]);

        self::assertInstanceOf(UserPreferences::class, $user->preferences);
        self::assertSame('foo', $user->preferences->lastFmSessionKey);

        $user->preferences->lastFmSessionKey = 'bar';
        $user->save();
        self::assertSame('bar', $user->refresh()->preferences->lastFmSessionKey);

        $user->preferences->lastFmSessionKey = null;
        $user->save();
        self::assertNull($user->refresh()->preferences->lastFmSessionKey);
    }

    #[Test]
    public function defaultValues(): void
    {
        $user = create_user();
        $prefs = $user->preferences;

        self::assertSame(7.0, $prefs->volume);
        self::assertSame('NO_REPEAT', $prefs->repeatMode);
        self::assertSame('thumbnails', $prefs->albumsViewMode);
        self::assertSame('thumbnails', $prefs->artistsViewMode);
        self::assertSame('thumbnails', $prefs->radioStationsViewMode);
        self::assertSame('name', $prefs->albumsSortField);
        self::assertSame('name', $prefs->artistsSortField);
        self::assertSame('name', $prefs->genresSortField);
        self::assertSame('title', $prefs->podcastsSortField);
        self::assertSame('name', $prefs->radioStationsSortField);
        self::assertSame('asc', $prefs->albumsSortOrder);
        self::assertSame('asc', $prefs->artistsSortOrder);
        self::assertSame('asc', $prefs->genresSortOrder);
        self::assertSame('asc', $prefs->podcastsSortOrder);
        self::assertSame('asc', $prefs->radioStationsSortOrder);
        self::assertFalse($prefs->albumsFavoritesOnly);
        self::assertFalse($prefs->artistsFavoritesOnly);
        self::assertFalse($prefs->podcastsFavoritesOnly);
        self::assertFalse($prefs->radioStationsFavoritesOnly);
        self::assertSame('classic', $prefs->theme);
        self::assertTrue($prefs->showNowPlayingNotification);
        self::assertFalse($prefs->confirmBeforeClosing);
        self::assertTrue($prefs->transcodeOnMobile);
        self::assertTrue($prefs->showAlbumArtOverlay);
        self::assertFalse($prefs->makeUploadsPublic);
        self::assertTrue($prefs->includePublicMedia);
        self::assertFalse($prefs->supportBarNoBugging);
        self::assertFalse($prefs->continuousPlayback);
        self::assertSame(0, $prefs->crossfadeDuration);
        self::assertSame(1, $prefs->lyricsZoomLevel);
        self::assertSame('default', $prefs->visualizer);
        self::assertNull($prefs->activeExtraPanelTab);
    }

    #[Test]
    public function castsVolume(): void
    {
        $user = create_user(['preferences' => ['volume' => 5.5]]);
        self::assertSame(5.5, $user->preferences->volume);

        $user->preferences->volume = 8.0;
        $user->save();
        self::assertSame(8.0, $user->refresh()->preferences->volume);
    }

    #[Test]
    public function castsRepeatMode(): void
    {
        $user = create_user(['preferences' => ['repeat_mode' => 'REPEAT_ALL']]);
        self::assertSame('REPEAT_ALL', $user->preferences->repeatMode);
    }

    #[Test]
    public function castsBooleanPreferences(): void
    {
        $user = create_user([
            'preferences' => [
                'continuous_playback' => true,
                'confirm_before_closing' => true,
                'show_album_art_overlay' => false,
                'make_uploads_public' => true,
            ],
        ]);

        self::assertTrue($user->preferences->continuousPlayback);
        self::assertTrue($user->preferences->confirmBeforeClosing);
        self::assertFalse($user->preferences->showAlbumArtOverlay);
        self::assertTrue($user->preferences->makeUploadsPublic);
    }

    #[Test]
    public function castsCrossfadeDuration(): void
    {
        $user = create_user(['preferences' => ['crossfade_duration' => 7]]);
        self::assertSame(7, $user->preferences->crossfadeDuration);

        $user->preferences->crossfadeDuration = 12;
        $user->save();
        self::assertSame(12, $user->refresh()->preferences->crossfadeDuration);
    }

    #[Test]
    public function castsTranscodeQuality(): void
    {
        $user = create_user(['preferences' => ['transcode_quality' => 256]]);
        self::assertSame(256, $user->preferences->transcodeQuality);
    }

    #[Test]
    public function castsViewModes(): void
    {
        $user = create_user([
            'preferences' => [
                'albums_view_mode' => 'list',
                'artists_view_mode' => 'list',
            ],
        ]);

        self::assertSame('list', $user->preferences->albumsViewMode);
        self::assertSame('list', $user->preferences->artistsViewMode);
    }

    #[Test]
    public function castsSortFields(): void
    {
        $user = create_user([
            'preferences' => [
                'albums_sort_field' => 'year',
                'albums_sort_order' => 'desc',
            ],
        ]);

        self::assertSame('year', $user->preferences->albumsSortField);
        self::assertSame('desc', $user->preferences->albumsSortOrder);
    }

    #[Test]
    public function castsActiveExtraPanelTab(): void
    {
        $user = create_user(['preferences' => ['active_extra_panel_tab' => 'Lyrics']]);
        self::assertSame('Lyrics', $user->preferences->activeExtraPanelTab);

        $user->preferences->activeExtraPanelTab = null;
        $user->save();
        self::assertNull($user->refresh()->preferences->activeExtraPanelTab);
    }

    #[Test]
    public function fallsBackToLegacyEqualizerKeyForBackwardsCompat(): void
    {
        $user = create_user([
            'preferences' => [
                'equalizer' => [
                    'name' => 'Rock',
                    'preamp' => 2,
                    'gains' => [4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
                ],
            ],
        ]);

        self::assertSame('Rock', $user->preferences->currentEqualizerPreset->name);
        self::assertSame(2.0, $user->preferences->currentEqualizerPreset->preamp);
    }

    #[Test]
    public function defaultsEqualizerPresetsToEmptyCollection(): void
    {
        $user = create_user();

        self::assertCount(0, $user->preferences->equalizerPresets);
    }

    #[Test]
    public function roundTripsEqualizerPresets(): void
    {
        $user = create_user([
            'preferences' => [
                'equalizer_presets' => [
                    [
                        'id' => '01J0000000000000000000ABCD',
                        'name' => 'My Bass Boost',
                        'preamp' => 3.0,
                        'gains' => [6, 5, 4, 3, 2, 1, 0, 0, 0, 0],
                    ],
                ],
            ],
        ]);

        $presets = $user->refresh()->preferences->equalizerPresets;

        self::assertCount(1, $presets);
        self::assertSame('01J0000000000000000000ABCD', $presets->first()->id);
        self::assertSame('My Bass Boost', $presets->first()->name);
        self::assertSame(3.0, $presets->first()->preamp);
        self::assertSame([6.0, 5.0, 4.0, 3.0, 2.0, 1.0, 0.0, 0.0, 0.0, 0.0], $presets->first()->gains);
    }

    #[Test]
    public function dropsMalformedEqualizerPresets(): void
    {
        $user = create_user([
            'preferences' => [
                'equalizer_presets' => [
                    ['id' => 'valid', 'name' => 'Good', 'preamp' => 0, 'gains' => array_fill(0, 10, 0)],
                    ['id' => 'no-name', 'preamp' => 0, 'gains' => array_fill(0, 10, 0)],
                    ['id' => 'short-gains', 'name' => 'Bad', 'preamp' => 0, 'gains' => [0, 0, 0]],
                    'totally-not-an-array',
                ],
            ],
        ]);

        $presets = $user->preferences->equalizerPresets;

        self::assertCount(1, $presets);
        self::assertSame('Good', $presets->first()->name);
    }

    #[Test]
    public function roundTripsAllPreferences(): void
    {
        $user = create_user([
            'preferences' => [
                'volume' => 3.5,
                'repeat_mode' => 'REPEAT_ONE',
                'theme' => 'classic',
                'continuous_playback' => true,
                'crossfade_duration' => 10,
                'lyrics_zoom_level' => 2,
                'visualizer' => 'default',
                'active_extra_panel_tab' => 'Album',
                'lastfm_session_key' => 'session123',
            ],
        ]);

        $prefs = $user->refresh()->preferences;

        self::assertSame(3.5, $prefs->volume);
        self::assertSame('REPEAT_ONE', $prefs->repeatMode);
        self::assertSame('classic', $prefs->theme);
        self::assertTrue($prefs->continuousPlayback);
        self::assertSame(10, $prefs->crossfadeDuration);
        self::assertSame(2, $prefs->lyricsZoomLevel);
        self::assertSame('default', $prefs->visualizer);
        self::assertSame('Album', $prefs->activeExtraPanelTab);
        self::assertSame('session123', $prefs->lastFmSessionKey);
    }
}
