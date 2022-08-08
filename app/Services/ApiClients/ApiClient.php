<?php

namespace App\Services\ApiClients;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Promise\Promise;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SimpleXMLElement;
use Webmozart\Assert\Assert;

/**
 * @method mixed get(string $uri, array $data = [], bool $appendKey = true)
 * @method mixed post($uri, array $data = [], bool $appendKey = true)
 * @method mixed put($uri, array $data = [], bool $appendKey = true)
 * @method mixed patch($uri, array $data = [], bool $appendKey = true)
 * @method mixed head($uri, array $data = [], bool $appendKey = true)
 * @method mixed delete($uri, array $data = [], bool $appendKey = true)
 * @method Promise getAsync(string $uri, array $data = [], bool $appendKey = true)
 * @method Promise postAsync($uri, array $data = [], bool $appendKey = true)
 * @method Promise putAsync($uri, array $data = [], bool $appendKey = true)
 * @method Promise patchAsync($uri, array $data = [], bool $appendKey = true)
 * @method Promise headAsync($uri, array $data = [], bool $appendKey = true)
 * @method Promise deleteAsync($uri, array $data = [], bool $appendKey = true)
 */
abstract class ApiClient
{
    private const MAGIC_METHODS = [
        'get',
        'post',
        'put',
        'patch',
        'head',
        'delete',
        'getAsync',
        'postAsync',
        'putAsync',
        'patchAsync',
        'headAsync',
        'deleteAsync',
    ];

    protected string $responseFormat = 'json';

    /**
     * The query parameter name for the key.
     * For example, Last.fm use api_key, like this:
     * https://ws.audioscrobbler.com/2.0?method=artist.getInfo&artist=Kamelot&api_key=API_KEY.
     */
    protected string $keyParam = 'key';

    public function __construct(protected GuzzleHttpClient $wrapped)
    {
    }

    /**
     * Make a request to the API.
     *
     * @param string $method The HTTP method
     * @param string $uri The API URI (segment)
     * @param bool $appendKey Whether to automatically append the API key into the URI.
     *                           While it's usually the case, some services (like Last.fm) requires
     *                           an "API signature" of the request. Appending an API key will break the request.
     * @param array $params An array of parameters
     *
     */
    public function request(string $method, string $uri, bool $appendKey = true, array $params = []): mixed
    {
        return attempt(function () use ($method, $uri, $appendKey, $params) {
            $body = (string) $this->wrapped
                ->$method($this->buildUrl($uri, $appendKey), ['form_params' => $params])
                ->getBody();

            if ($this->responseFormat === 'json') {
                return json_decode($body);
            }

            if ($this->responseFormat === 'xml') {
                return simplexml_load_string($body);
            }

            return $body;
        });
    }

    public function requestAsync(string $method, string $uri, bool $appendKey = true, array $params = []): Promise
    {
        return $this->wrapped->$method($this->buildUrl($uri, $appendKey), ['form_params' => $params]);
    }

    /**
     * Make an HTTP call to the external resource.
     *
     * @param string $method The HTTP method
     * @param array<mixed> $args An array of parameters
     *
     * @return mixed|SimpleXMLElement|void
     * @throws InvalidArgumentException
     *
     */
    public function __call(string $method, array $args) // @phpcs:ignore
    {
        Assert::inArray($method, self::MAGIC_METHODS);

        if (count($args) < 1) {
            throw new InvalidArgumentException('Magic request methods require a URI and optional options array');
        }

        $uri = $args[0];
        $params = $args[1] ?? [];
        $appendKey = $args[2] ?? true;

        if (Str::endsWith($method, 'Async')) {
            return $this->requestAsync($method, $uri, $appendKey, $params);
        } else {
            return $this->request($method, $uri, $appendKey, $params);
        }
    }

    /**
     * Turn a URI segment into a full API URL.
     *
     * @param bool $appendKey whether to automatically append the API key into the URL
     */
    public function buildUrl(string $uri, bool $appendKey = true): string
    {
        if (!starts_with($uri, ['http://', 'https://'])) {
            if ($uri[0] !== '/') {
                $uri = "/$uri";
            }

            $uri = $this->getEndpoint() . $uri;
        }

        if ($appendKey) {
            if (parse_url($uri, PHP_URL_QUERY)) {
                $uri .= "&$this->keyParam=" . $this->getKey();
            } else {
                $uri .= "?$this->keyParam=" . $this->getKey();
            }
        }

        return $uri;
    }

    abstract public function getKey(): ?string;

    abstract public function getSecret(): ?string;

    abstract public function getEndpoint(): ?string;
}
