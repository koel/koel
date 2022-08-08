<?php

namespace App\Services\ApiClients;

use GuzzleHttp\Promise\Promise;

class LastfmClient extends ApiClient
{
    protected string $keyParam = 'api_key';

    public function post($uri, array $data = [], bool $appendKey = true): mixed
    {
        return parent::post($uri, $this->buildAuthCallParams($data), $appendKey);
    }

    public function postAsync($uri, array $data = [], bool $appendKey = true): Promise
    {
        return parent::postAsync($uri, $this->buildAuthCallParams($data), $appendKey);
    }

    /**
     * Get Last.fm's session key for the authenticated user using a token.
     *
     * @param string $token The token after successfully connecting to Last.fm
     *
     * @see http://www.last.fm/api/webauth#4
     */
    public function getSessionKey(string $token): ?string
    {
        $query = $this->buildAuthCallParams([
            'method' => 'auth.getSession',
            'token' => $token,
        ], true);


        return attempt(fn () => $this->get("/?$query&format=json", [], false)->session->key);
    }

    /**
     * Build the parameters to use for _authenticated_ Last.fm API calls.
     * Such calls require:
     * - The API key (api_key)
     * - The API signature (api_sig).
     *
     * @see http://www.last.fm/api/webauth#5
     *
     * @param array $params The array of parameters
     * @param bool $toString Whether to turn the array into a query string
     *
     * @return array<mixed>|string
     */
    private function buildAuthCallParams(array $params, bool $toString = false): array|string
    {
        $params['api_key'] = $this->getKey();
        ksort($params);

        // Generate the API signature.
        // @link http://www.last.fm/api/webauth#6
        $str = '';

        foreach ($params as $name => $value) {
            $str .= $name . $value;
        }

        $str .= $this->getSecret();
        $params['api_sig'] = md5($str);

        if (!$toString) {
            return $params;
        }

        $query = '';

        foreach ($params as $key => $value) {
            $query .= "$key=$value&";
        }

        return rtrim($query, '&');
    }

    public function getKey(): ?string
    {
        return config('koel.lastfm.key');
    }

    public function getEndpoint(): ?string
    {
        return config('koel.lastfm.endpoint');
    }

    public function getSecret(): ?string
    {
        return config('koel.lastfm.secret');
    }
}
