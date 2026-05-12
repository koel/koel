<?php

namespace Tests\Unit\Values\User\Preferences;

use App\Values\EqualizerPreset;
use App\Values\EqualizerPresetCollection;
use App\Values\User\Preferences\ActiveExtraPanelTabPreference;
use App\Values\User\Preferences\AlbumsSortFieldPreference;
use App\Values\User\Preferences\AlbumsSortOrderPreference;
use App\Values\User\Preferences\AlbumsViewModePreference;
use App\Values\User\Preferences\CrossfadeDurationPreference;
use App\Values\User\Preferences\CurrentEqualizerPresetPreference;
use App\Values\User\Preferences\EqualizerPresetsPreference;
use App\Values\User\Preferences\LastfmSessionKeyPreference;
use App\Values\User\Preferences\MakeUploadsPublicPreference;
use App\Values\User\Preferences\RepeatModePreference;
use App\Values\User\Preferences\TranscodeQualityPreference;
use App\Values\User\Preferences\VolumePreference;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Webmozart\Assert\InvalidArgumentException as AssertException;

class PreferenceBehaviorTest extends TestCase
{
    #[Test]
    public function volumeCastsToFloat(): void
    {
        self::assertSame(7.5, VolumePreference::make('7.5')->getValue());
        self::assertSame(0.0, VolumePreference::make(0)->getValue());
        self::assertSame(7.0, VolumePreference::make(null)->getValue());
    }

    #[Test]
    public function repeatModeRejectsUnknownValue(): void
    {
        $this->expectException(AssertException::class);
        RepeatModePreference::make('SHUFFLE');
    }

    #[Test]
    public function repeatModeAcceptsValidValues(): void
    {
        foreach (['NO_REPEAT', 'REPEAT_ALL', 'REPEAT_ONE'] as $value) {
            self::assertSame($value, RepeatModePreference::make($value)->getValue());
        }
    }

    #[Test]
    public function albumsViewModeRejectsUnknownValue(): void
    {
        $this->expectException(AssertException::class);
        AlbumsViewModePreference::make('grid');
    }

    #[Test]
    public function albumsSortFieldRejectsUnknownValue(): void
    {
        $this->expectException(AssertException::class);
        AlbumsSortFieldPreference::make('unknown_column');
    }

    #[Test]
    public function albumsSortOrderNormalizesToLowercase(): void
    {
        self::assertSame('asc', AlbumsSortOrderPreference::make('ASC')->getValue());
        self::assertSame('desc', AlbumsSortOrderPreference::make('Desc')->getValue());
    }

    #[Test]
    public function activeExtraPanelTabAcceptsNull(): void
    {
        self::assertNull(ActiveExtraPanelTabPreference::make(null)->getValue());
    }

    #[Test]
    public function activeExtraPanelTabRejectsUnknownTab(): void
    {
        $this->expectException(AssertException::class);
        ActiveExtraPanelTabPreference::make('Settings');
    }

    #[Test]
    public function makeUploadsPublicCoercesTruthyStrings(): void
    {
        self::assertTrue(MakeUploadsPublicPreference::make('true')->getValue());
        self::assertTrue(MakeUploadsPublicPreference::make(1)->getValue());
        self::assertFalse(MakeUploadsPublicPreference::make('false')->getValue());
        self::assertFalse(MakeUploadsPublicPreference::make(0)->getValue());
        self::assertFalse(MakeUploadsPublicPreference::make(null)->getValue());
    }

    #[Test]
    public function crossfadeDurationCastsToInt(): void
    {
        self::assertSame(5, CrossfadeDurationPreference::make('5')->getValue());
    }

    #[Test]
    public function crossfadeDurationRejectsOutOfRange(): void
    {
        $this->expectException(AssertException::class);
        CrossfadeDurationPreference::make(20);
    }

    #[Test]
    public function transcodeQualityFallsBackTo128ForUnknownBitrate(): void
    {
        self::assertSame(128, TranscodeQualityPreference::make(99)->getValue());
        self::assertSame(256, TranscodeQualityPreference::make(256)->getValue());
    }

    #[Test]
    public function lastfmSessionKeyIsNotCustomizable(): void
    {
        self::assertFalse((new LastfmSessionKeyPreference())->isCustomizable());
        self::assertSame('lastFmSessionKey', (new LastfmSessionKeyPreference())->getProperty());
        self::assertSame('lastfm_session_key', (new LastfmSessionKeyPreference())->getKey());
    }

    #[Test]
    public function currentEqualizerPresetExposesLegacyAlias(): void
    {
        $aliases = (new CurrentEqualizerPresetPreference())->getAliases();
        self::assertSame(['equalizer'], $aliases);
    }

    #[Test]
    public function currentEqualizerPresetParsesArray(): void
    {
        $value = CurrentEqualizerPresetPreference::make([
            'name' => 'Rock',
            'preamp' => 2.0,
            'gains' => [4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
        ])->getValue();

        self::assertInstanceOf(EqualizerPreset::class, $value);
        self::assertSame('Rock', $value->name);
    }

    #[Test]
    public function currentEqualizerPresetPassesThroughInstance(): void
    {
        $preset = EqualizerPreset::default();
        self::assertSame($preset, CurrentEqualizerPresetPreference::make($preset)->getValue());
    }

    #[Test]
    public function equalizerPresetsParsesArrayOfPresets(): void
    {
        $value = EqualizerPresetsPreference::make([
            ['id' => '01J0000000000000000000ABCD', 'name' => 'Mine', 'preamp' => 0, 'gains' => array_fill(0, 10, 0)],
        ])->getValue();

        self::assertInstanceOf(EqualizerPresetCollection::class, $value);
        self::assertCount(1, $value);
        self::assertSame('Mine', $value->first()->name);
    }

    #[Test]
    public function unknownPropertyAccessThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        VolumePreference::make(7.0)->somethingWrong; // @phpstan-ignore-line
    }
}
