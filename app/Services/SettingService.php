<?php

namespace App\Services;

use App\Facades\License;
use App\Models\Organization;
use App\Models\Setting;
use App\Services\Image\ImageStorage;
use App\Values\AiSettings;
use App\Values\Branding;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;
use SensitiveParameter;

class SettingService
{
    public function __construct(
        private readonly ImageStorage $imageStorage,
    ) {}

    public function getBranding(Organization $organization): Branding
    {
        return License::isPlus()
            ? Branding::fromArray(Arr::wrap(Setting::get('branding', $organization)))
            : Branding::make(name: config('app.name'));
    }

    public function getAiSettings(Organization $organization): AiSettings
    {
        $stored = Arr::wrap(Setting::get('ai', $organization));
        $encryptedApiKey = Arr::get($stored, 'api_key');

        return AiSettings::make(
            enabled: (bool) Arr::get($stored, 'enabled'),
            provider: Lab::tryFrom((string) Arr::get($stored, 'provider')),
            apiKey: $encryptedApiKey ? self::decryptApiKey($encryptedApiKey) : null,
        );
    }

    private static function decryptApiKey(#[SensitiveParameter] string $encryptedApiKey): ?string
    {
        try {
            return Crypt::decryptString($encryptedApiKey);
        } catch (DecryptException) {
            Log::warning('The stored AI API key could not be decrypted, most likely because APP_KEY changed.');

            return null;
        }
    }

    /**
     * @param ?string $apiKey a new key, or null to keep the stored one; a changed provider drops the stored key
     */
    public function updateAiSettings(
        Organization $organization,
        bool $enabled,
        Lab $provider,
        #[SensitiveParameter]
        ?string $apiKey,
    ): void {
        $current = $this->getAiSettings($organization);
        $apiKey ??= $current->provider === $provider ? $current->apiKey : null;

        Setting::set(
            'ai',
            [
                'enabled' => $enabled,
                'provider' => $provider->value,
                'api_key' => $apiKey ? Crypt::encryptString($apiKey) : null,
            ],
            $organization,
        );
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

    public function updateBranding(Organization $organization, string $name, ?string $logo, ?string $cover): void
    {
        $branding = $this->getBranding($organization)->withName($name);

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

        Setting::set('branding', $branding->toArray(), $organization);
    }
}
