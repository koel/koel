<?php

namespace App\Services;

use App\Facades\License;
use App\Models\Setting;
use App\Services\Image\ImageStorage;
use App\Values\Branding;
use Illuminate\Support\Arr;

class SettingService
{
    public function __construct(
        private readonly ImageStorage $imageStorage,
    ) {}

    public function getBranding(): Branding
    {
        return License::isPlus()
            ? Branding::fromArray(Arr::wrap(Setting::get('branding')))
            : Branding::make(name: config('app.name'));
    }

    public function updateMediaPath(string $path): string
    {
        $path = self::canonicalizeMediaPath($path);
        Setting::set('media_path', $path);

        return $path;
    }

    private static function canonicalizeMediaPath(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $sep = preg_quote(DIRECTORY_SEPARATOR, '#');
        $path = preg_replace('#' . $sep . '+#', DIRECTORY_SEPARATOR, $path);

        if ($path === DIRECTORY_SEPARATOR) {
            return DIRECTORY_SEPARATOR;
        }

        $path = rtrim($path, DIRECTORY_SEPARATOR);

        $real = realpath($path);

        return $real ?: $path;
    }

    public function updateBranding(string $name, ?string $logo, ?string $cover): void
    {
        $branding = $this->getBranding()->withName($name);

        if ($logo && $logo !== $branding->logo) {
            $branding = $branding->withLogo($this->imageStorage->storeImage($logo));
        } elseif (!$logo) {
            $branding = $branding->withoutLogo();
        }

        if ($cover && $cover !== $branding->cover) {
            $branding = $branding->withCover($this->imageStorage->storeImage($cover));
        } elseif (!$cover) {
            $branding = $branding->withoutCover();
        }

        Setting::set('branding', $branding->toArray());
    }
}
