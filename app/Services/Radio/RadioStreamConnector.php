<?php

namespace App\Services\Radio;

use App\Services\Network\Network;
use Illuminate\Support\Uri;

class RadioStreamConnector
{
    public function __construct(
        private readonly Network $network,
    ) {}

    /**
     * Open a stream to the radio station URL, requesting ICY metadata.
     * Falls back to a plain connection if the ICY request fails.
     *
     * @return resource|false
     */
    public function connect(string $url)
    {
        $publicIps = $this->network->resolveUrlToPublicIps($url);

        if (!$publicIps) {
            return false;
        }

        // fopen() resolves the host itself, so validating the URL and then handing fopen()
        // the same hostname leaves a window for the answer to change to a private address
        // in between. Dial the address that was validated and carry the original host in
        // the Host header and the TLS peer name, the way SafeHttp pins curl via
        // CURLOPT_RESOLVE. Redirects stay off, so this is the only host ever contacted.
        $host = Uri::of($url)->host();
        $pinnedUrl = (string) Uri::of($url)->withHost(self::formatHostForUrl($publicIps[0]));

        $httpOptions = [
            'timeout' => 5,
            'follow_location' => 0,
            'max_redirects' => 0,
        ];

        $sslOptions = [
            'peer_name' => $host,
            'SNI_enabled' => true,
        ];

        $context = stream_context_create([
            'http' => ['header' => "Icy-MetaData: 1\r\nHost: $host\r\n", ...$httpOptions],
            'ssl' => $sslOptions,
        ]);

        $plainContext = stream_context_create([
            'http' => ['header' => "Host: $host\r\n", ...$httpOptions],
            'ssl' => $sslOptions,
        ]);

        set_error_handler(static fn () => true);

        try {
            $stream = fopen($pinnedUrl, 'r', false, $context);

            if ($stream !== false) {
                return $stream;
            }

            return fopen($pinnedUrl, 'r', false, $plainContext);
        } finally {
            restore_error_handler();
        }
    }

    private static function formatHostForUrl(string $ip): string
    {
        return str_contains($ip, ':') ? "[$ip]" : $ip;
    }
}
