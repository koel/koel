<?php

namespace App\Services;

use App\Facades\License;
use App\Models\Setting;
use App\Values\Branding;
use Illuminate\Support\Arr;

class SettingService
{
    public function __construct(private readonly ImageStorage $imageStorage)
    {
    }

    public function getBranding(): Branding
    {
        return License::isPlus()
            ? Branding::fromArray(Arr::wrap(Setting::get('branding')))
            : Branding::make(name: config('app.name'));
    }

    public function updateMediaPath(string $path): string
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        Setting::set('media_path', $path);

        return $path;
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
