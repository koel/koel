<?php

namespace App\Services\Integrations;

use App\Http\Integrations\YouTube\Requests\SearchVideosRequest;
use App\Http\Integrations\YouTube\YouTubeConnector;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\Song;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use SensitiveParameter;
use Throwable;

class YouTubeService
{
    public function __construct(
        #[Config('koel.services.youtube.key')]
        #[SensitiveParameter]
        private readonly ?string $installWideApiKey,
    ) {}

    public function enabled(Organization $organization): bool
    {
        return (bool) $this->getApiKey($organization);
    }

    public function getApiKey(Organization $organization): ?string
    {
        return self::getStoredApiKey($organization) ?: $this->installWideApiKey;
    }

    public function hasStoredApiKey(Organization $organization): bool
    {
        return (bool) self::getStoredApiKey($organization);
    }

    private static function getStoredApiKey(Organization $organization): ?string
    {
        $encryptedApiKey = Setting::get('youtube_api_key', $organization);

        if (!$encryptedApiKey) {
            return null;
        }

        try {
            return Crypt::decryptString($encryptedApiKey);
        } catch (DecryptException) {
            Log::warning('The stored YouTube API key could not be decrypted, most likely because APP_KEY changed.');

            return null;
        }
    }

    /**
     * @param ?string $apiKey the new key, or null to remove the stored one
     */
    public function updateApiKey(Organization $organization, #[SensitiveParameter] ?string $apiKey): void
    {
        Setting::set('youtube_api_key', $apiKey ? Crypt::encryptString($apiKey) : '', $organization);
    }

    // @mago-ignore lint:sensitive-parameter
    public function searchVideosRelatedToSong(Song $song, Organization $organization, string $pageToken = ''): ?object
    {
        $apiKey = $this->getApiKey($organization);

        if (!$apiKey) {
            return null;
        }

        $connector = new YouTubeConnector($apiKey);
        $request = new SearchVideosRequest($song, $pageToken);

        try {
            return Cache::remember(
                cache_key('YouTube search query', serialize($request->query()->all())),
                now()->addWeek(),
                static fn () => $connector->send($request)->throw()->object(),
            );
        } catch (Throwable) {
            return null;
        }
    }
}
