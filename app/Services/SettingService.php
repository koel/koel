<?php

namespace App\Services;

use App\Enums\AiProvider;
use App\Enums\AiSettingsSource;
use App\Facades\License;
use App\Models\Organization;
use App\Models\Setting;
use App\Services\Image\ImageStorage;
use App\Values\AiSettings;
use App\Values\Branding;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use SensitiveParameter;

class SettingService
{
    /**
     * @param array<string, array<string, mixed>> $aiProviders
     */
    public function __construct(
        private readonly ImageStorage $imageStorage,
        #[Config('koel.ai.enabled')]
        private readonly bool $aiEnabledInEnvironment = false,
        #[Config('ai.default')]
        private readonly ?string $defaultAiProvider = null,
        #[Config('ai.providers')]
        private readonly array $aiProviders = [],
    ) {}

    public function getBranding(): Branding
    {
        return License::isPlus()
            ? Branding::fromArray(Arr::wrap(Setting::get('branding')))
            : Branding::make(name: config('app.name'));
    }

    public function getAiSettings(Organization $organization): AiSettings
    {
        $stored = Setting::get('ai', $organization);

        if ($stored === null) {
            return AiSettings::make(
                source: AiSettingsSource::Environment,
                enabled: $this->aiEnabledInEnvironment,
                provider: $this->defaultAiProvider,
                apiKey: Arr::get($this->aiProviders, "{$this->defaultAiProvider}.key"),
            );
        }

        $stored = Arr::wrap($stored);
        $encryptedApiKey = Arr::get($stored, 'api_key');

        return AiSettings::make(
            source: AiSettingsSource::Organization,
            enabled: (bool) Arr::get($stored, 'enabled'),
            provider: Arr::get($stored, 'provider'),
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
     * @param ?string $apiKey a new key, or null to keep the organization's key for the same provider
     */
    public function updateAiSettings(
        Organization $organization,
        bool $enabled,
        AiProvider $provider,
        #[SensitiveParameter]
        ?string $apiKey,
    ): void {
        $current = $this->getAiSettings($organization);

        if ($apiKey === null && $current->hasOrganizationKeyFor($provider->value)) {
            $apiKey = $current->apiKey;
        }

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
