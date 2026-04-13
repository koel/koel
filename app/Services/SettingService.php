<?php

namespace App\Services;

use App\Facades\License;
use App\Models\Setting;
use App\Values\Branding;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Arr;

class SettingService
{
    public function __construct(
        private readonly ImageStorage $imageStorage,
        #[Config('koel.legal.terms_url')] private readonly ?string $termsUrl = null,
        #[Config('koel.legal.privacy_url')] private readonly ?string $privacyUrl = null,
    ) {}

    /** @return array{terms_url: ?string, privacy_url: ?string} */
    public function getConsentLegalUrls(): array
    {
        return [
            'terms_url' => $this->termsUrl,
            'privacy_url' => $this->privacyUrl,
        ];
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
