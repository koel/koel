<?php

namespace Tests\Unit\Values\User\Preferences;

use App\Values\User\Preferences\AlbumsFavoritesOnlyPreference;
use App\Values\User\Preferences\ArtistsFavoritesOnlyPreference;
use App\Values\User\Preferences\ConfirmBeforeClosingPreference;
use App\Values\User\Preferences\ContinuousPlaybackPreference;
use App\Values\User\Preferences\CrossfadeDurationPreference;
use App\Values\User\Preferences\DetectDuplicateUploadsPreference;
use App\Values\User\Preferences\IncludePublicMediaPreference;
use App\Values\User\Preferences\LastfmSessionKeyPreference;
use App\Values\User\Preferences\LyricsZoomLevelPreference;
use App\Values\User\Preferences\MakeUploadsPublicPreference;
use App\Values\User\Preferences\PodcastsFavoritesOnlyPreference;
use App\Values\User\Preferences\Preference;
use App\Values\User\Preferences\RadioStationsFavoritesOnlyPreference;
use App\Values\User\Preferences\ShowAlbumArtOverlayPreference;
use App\Values\User\Preferences\ShowNowPlayingNotificationPreference;
use App\Values\User\Preferences\SupportBarNoBuggingPreference;
use App\Values\User\Preferences\TranscodeOnMobilePreference;
use Generator;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class PreferenceContractTest extends TestCase
{
    /** @param class-string<Preference> $class */
    #[Test]
    #[DataProvider('preferenceClasses')]
    public function keyIsSnakeCase(string $class): void
    {
        $key = (new $class())->getKey();
        self::assertNotEmpty($key);
        self::assertSame($key, Str::snake($key), "Key {$key} on {$class} is not snake_case");
        self::assertDoesNotMatchRegularExpression('/[A-Z]/', $key);
    }

    /** @param class-string<Preference> $class */
    #[Test]
    #[DataProvider('preferenceClasses')]
    public function propertyIsCamelCase(string $class): void
    {
        $property = (new $class())->getProperty();
        self::assertNotEmpty($property);
        self::assertDoesNotMatchRegularExpression('/[_\s]/', $property);
        self::assertDoesNotMatchRegularExpression('/^[A-Z]/', $property);
    }

    /** @param class-string<Preference> $class */
    #[Test]
    #[DataProvider('preferenceClasses')]
    public function makeWithNullYieldsDefaultValue(string $class): void
    {
        $proto = new $class();
        $instance = $class::make(null);

        self::assertEquals($proto->getDefaultValue(), $instance->getValue());
    }

    /** @param class-string<Preference> $class */
    #[Test]
    #[DataProvider('preferenceClasses')]
    public function defaultValuePassesAssert(string $class): void
    {
        $instance = $class::make(null);
        $instance->assert();

        $this->addToAssertionCount(1);
    }

    /** @param class-string<Preference> $class */
    #[Test]
    #[DataProvider('preferenceClasses')]
    public function customizableDefaultsTrueExceptForLastFmSessionKey(string $class): void
    {
        $customizable = (new $class())->isCustomizable();

        if ($class === LastfmSessionKeyPreference::class) {
            self::assertFalse($customizable);
        } else {
            self::assertTrue($customizable);
        }
    }

    /** @param class-string<Preference> $class */
    #[Test]
    #[DataProvider('booleanPreferences')]
    public function booleanPreferencesCoerceTruthyAndFalsyInputs(string $class): void
    {
        foreach ([true, 1, '1', 'true', 'on', 'yes'] as $truthy) {
            self::assertTrue($class::make($truthy)->getValue(), 'Expected truthy: ' . var_export($truthy, true));
        }

        foreach ([false, 0, '0', 'false', 'off', 'no', ''] as $falsy) {
            self::assertFalse($class::make($falsy)->getValue(), 'Expected falsy: ' . var_export($falsy, true));
        }
    }

    /** @param class-string<Preference> $class */
    #[Test]
    #[DataProvider('integerPreferences')]
    public function integerPreferencesCastNumericStringsAndFloats(string $class): void
    {
        $cast = $class::make('5')->getValue();
        self::assertIsInt($cast);
        self::assertSame(5, $cast);

        $cast = $class::make(7.9)->getValue();
        self::assertIsInt($cast);
        self::assertSame(7, $cast);
    }

    /** @return Generator<string, array{class-string<Preference>}> */
    public static function preferenceClasses(): Generator
    {
        $namespace = 'App\\Values\\User\\Preferences\\';
        $directory = dirname(__DIR__, 5) . '/app/Values/User/Preferences';

        foreach (glob($directory . '/*Preference.php') ?: [] as $file) {
            $class = $namespace . pathinfo($file, PATHINFO_FILENAME);

            if (!is_subclass_of($class, Preference::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            yield $reflection->getShortName() => [$class];
        }
    }

    /** @return Generator<string, array{class-string<Preference>}> */
    public static function booleanPreferences(): Generator
    {
        $classes = [
            AlbumsFavoritesOnlyPreference::class,
            ArtistsFavoritesOnlyPreference::class,
            PodcastsFavoritesOnlyPreference::class,
            RadioStationsFavoritesOnlyPreference::class,
            ConfirmBeforeClosingPreference::class,
            MakeUploadsPublicPreference::class,
            SupportBarNoBuggingPreference::class,
            ContinuousPlaybackPreference::class,
            ShowNowPlayingNotificationPreference::class,
            TranscodeOnMobilePreference::class,
            ShowAlbumArtOverlayPreference::class,
            DetectDuplicateUploadsPreference::class,
            IncludePublicMediaPreference::class,
        ];

        foreach ($classes as $class) {
            yield (new ReflectionClass($class))->getShortName() => [$class];
        }
    }

    /** @return Generator<string, array{class-string<Preference>}> */
    public static function integerPreferences(): Generator
    {
        foreach ([CrossfadeDurationPreference::class, LyricsZoomLevelPreference::class] as $class) {
            yield (new ReflectionClass($class))->getShortName() => [$class];
        }
    }
}
