<?php

namespace App\Services;

use App\Exceptions\SpotifyIntegrationDisabledException;
use Illuminate\Cache\Repository as Cache;
use Psr\Log\LoggerInterface;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;
use Throwable;

/**
 * @method array search(string $keywords, string|array $type, array|object $options = [])
 */
class SpotifyClient
{
    public function __construct(
        public SpotifyWebAPI $wrapped,
        private ?Session $session,
        private Cache $cache,
        private LoggerInterface $log
    ) {
        if (SpotifyService::enabled()) {
            $this->wrapped->setOptions(['return_assoc' => true]);

            try {
                $this->setAccessToken();
            } catch (Throwable $e) {
                $this->log->error('Failed to set Spotify access token', ['exception' => $e]);
            }
        }
    }

    private function setAccessToken(): void
    {
        $token = $this->cache->get('spotify.access_token');

        if (!$token) {
            $this->session->requestCredentialsToken();
            $token = $this->session->getAccessToken();

            // Spotify's tokens expire after 1 hour, so we'll cache them with some buffer to an extra call.
            $this->cache->put('spotify.access_token', $token, 59 * 60);
        }

        $this->wrapped->setAccessToken($token);
    }

    public function __call(string $name, array $arguments): mixed
    {
        throw_unless(SpotifyService::enabled(), SpotifyIntegrationDisabledException::create());

        return $this->wrapped->$name(...$arguments);
    }
}
