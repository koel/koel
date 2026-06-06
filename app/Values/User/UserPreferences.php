<?php

namespace App\Values\User;

use App\Values\EqualizerPreset;
use App\Values\EqualizerPresetCollection;
use App\Values\User\Preferences\Preference;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use JsonSerializable;
use ReflectionClass;
use Webmozart\Assert\Assert;

// @mago-ignore lint:too-many-methods

/**
 * @property float $volume
 * @property string $repeatMode
 * @property EqualizerPreset $currentEqualizerPreset
 * @property EqualizerPresetCollection $equalizerPresets
 * @property string $albumsViewMode
 * @property string $artistsViewMode
 * @property string $radioStationsViewMode
 * @property string $albumsSortField
 * @property string $artistsSortField
 * @property string $genresSortField
 * @property string $podcastsSortField
 * @property string $radioStationsSortField
 * @property string $albumsSortOrder
 * @property string $artistsSortOrder
 * @property string $genresSortOrder
 * @property string $podcastsSortOrder
 * @property string $radioStationsSortOrder
 * @property bool $albumsFavoritesOnly
 * @property bool $artistsFavoritesOnly
 * @property bool $podcastsFavoritesOnly
 * @property bool $radioStationsFavoritesOnly
 * @property string $theme
 * @property bool $showNowPlayingNotification
 * @property bool $confirmBeforeClosing
 * @property bool $transcodeOnMobile
 * @property int $transcodeQuality
 * @property bool $showAlbumArtOverlay
 * @property bool $makeUploadsPublic
 * @property bool $detectDuplicateUploads
 * @property bool $includePublicMedia
 * @property bool $supportBarNoBugging
 * @property bool $continuousPlayback
 * @property int $crossfadeDuration
 * @property int $lyricsZoomLevel
 * @property string $visualizer
 * @property ?string $activeExtraPanelTab
 * @property ?string $lastFmSessionKey
 */
final class UserPreferences implements Arrayable, JsonSerializable
{
    /** @var Collection<int, class-string<Preference>>|null */
    private static ?Collection $discoveredClasses = null;

    /** @param Collection<string, Preference> $preferences */
    private function __construct(
        private Collection $preferences,
    ) {}

    public static function fromArray(array $data): self
    {
        $preferences = self::preferenceClasses()
            ->mapWithKeys(static function (string $class) use ($data): array {
                $proto = new $class();
                $raw = self::readWithAliases($data, $proto);

                return [$proto->getKey() => $proto::make($raw)];
            });

        return new self($preferences);
    }

    public function __get(string $property): mixed
    {
        return $this->requirePreferenceByProperty($property)->getValue();
    }

    public function __set(string $property, mixed $value): void
    {
        $preference = $this->requirePreferenceByProperty($property);
        $this->preferences->put($preference->getKey(), $preference::make($value));
    }

    public function __isset(string $property): bool
    {
        $preference = $this->preferenceByProperty($property);

        return $preference !== null && $preference->getValue() !== null;
    }

    public static function customizable(string $key): bool
    {
        $class = self::classByKey($key);

        return $class !== null && (new $class())->isCustomizable();
    }

    public function set(string $key, mixed $value): self
    {
        $class = self::classByKey($key);
        Assert::notNull($class, "Unknown preference key: {$key}");

        $copy = clone $this;
        $copy->preferences = $copy->preferences->put($key, $class::make($value));

        return $copy;
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return $this->preferences->map(static fn (Preference $preference): mixed => self::serializeValue(
            $preference->getValue(),
        ))->all();
    }

    /** @inheritdoc */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __clone(): void
    {
        $this->preferences = clone $this->preferences;
    }

    /** @return Collection<int, class-string<Preference>> */
    private static function preferenceClasses(): Collection
    {
        if (self::$discoveredClasses !== null) {
            return self::$discoveredClasses;
        }

        $namespace = 'App\\Values\\User\\Preferences\\';

        $classes = collect(glob(__DIR__ . '/Preferences/*Preference.php') ?: [])
            ->map(static fn (string $file): string => $namespace . pathinfo($file, PATHINFO_FILENAME))
            ->filter(
                static fn (string $candidate): bool => (
                    is_subclass_of($candidate, Preference::class) && !(new ReflectionClass($candidate))->isAbstract()
                ),
            )
            ->values();

        self::$discoveredClasses = $classes;

        return $classes;
    }

    private function preferenceByProperty(string $property): ?Preference
    {
        return $this->preferences->first(
            static fn (Preference $preference): bool => $preference->getProperty() === $property,
        );
    }

    private function requirePreferenceByProperty(string $property): Preference
    {
        $preference = $this->preferenceByProperty($property);
        Assert::notNull($preference, "Unknown preference property: {$property}");

        return $preference;
    }

    /** @return class-string<Preference>|null */
    private static function classByKey(string $key): ?string
    {
        return self::preferenceClasses()->first(static fn (string $class): bool => (new $class())->getKey() === $key);
    }

    private static function readWithAliases(array $data, Preference $proto): mixed
    {
        $key = $proto->getKey();

        if (Arr::exists($data, $key)) {
            return $data[$key];
        }

        foreach ($proto->getAliases() as $alias) {
            if (Arr::exists($data, $alias)) {
                return $data[$alias];
            }
        }

        return null;
    }

    private static function serializeValue(mixed $value): mixed
    {
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        if (is_iterable($value) && !is_array($value)) {
            $items = [];

            foreach ($value as $item) {
                $items[] = $item instanceof Arrayable ? $item->toArray() : $item;
            }

            return $items;
        }

        return $value;
    }
}
