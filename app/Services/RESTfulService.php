<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use InvalidArgumentException;

/**
 * Class RESTfulService.
 *
 * @method object get($uri)
 * @method object post($uri, array $data = [])
 * @method object put($uri, array $data = [])
 * @method object patch($uri, array $data = [])
 * @method object head($uri, array $data = [])
 * @method object delete($uri)
 */
class RESTfulService
{
    protected $responseFormat = 'json';

    /**
     * The API endpoint.
     *
     * @var string
     */
    protected $endpoint = null;

    /**
     * The GuzzleHttp client to talk to the API.
     *
     * @var Client;
     */
    protected $client;

    /**
     * The API key.
     *
     * @var string
     */
    protected $key;

    /**
     * The query parameter name for the key.
     * For example, Last.fm use api_key, like this:
     * https://ws.audioscrobbler.com/2.0?method=artist.getInfo&artist=Kamelot&api_key=API_KEY.
     *
     * @var string
     */
    protected $keyParam = 'key';

    /**
     * The API secret.
     *
     * @var string
     */
    protected $secret;

    public function __construct($key, $secret, $endpoint, Client $client)
    {
        $this->setKey($key);
        $this->setSecret($secret);
        $this->setEndpoint($endpoint);
        $this->setClient($client);
    }

    /**
     * Make a request to the API.
     *
     * @param string $verb   The HTTP verb
     * @param string $uri    The API URI (segment)
     * @param array  $params An array of parameters
     *
     * @return object The JSON response.
     */
    public function request($verb, $uri, $params = [])
    {
        try {
            $body = (string) $this->getClient()
                ->$verb($this->buildUrl($uri), ['form_params' => $params])
                ->getBody();

            if ($this->responseFormat === 'json') {
                return json_decode($body);
            }

            if ($this->responseFormat === 'xml') {
                return simplexml_load_string($body);
            }

            return $body;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * Make an HTTP call to the external resource.
     *
     * @param string $method The HTTP method
     * @param array  $args   An array of parameters
     *
     * @return object
     */
    public function __call($method, $args)
    {
        if (count($args) < 1) {
            throw new InvalidArgumentException('Magic request methods require a URI and optional options array');
        }

        $uri = $args[0];
        $opts = isset($args[1]) ? $args[1] : [];

        return $this->request($method, $uri, $opts);
    }

    /**
     * Turn a URI segment into a full API URL.
     *
     * @param string $uri
     *
     * @return string
     */
    public function buildUrl($uri)
    {
        if (!starts_with($uri, ['http://', 'https://'])) {
            if ($uri[0] != '/') {
                $uri = "/$uri";
            }

            $uri = $this->endpoint.$uri;
        }

        // Append the API key.
        if (parse_url($uri, PHP_URL_QUERY)) {
            $uri .= "&{$this->keyParam}=".$this->getKey();
        } else {
            $uri .= "?{$this->keyParam}=".$this->getKey();
        }

        return $uri;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @param string $endpoint
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }
}
