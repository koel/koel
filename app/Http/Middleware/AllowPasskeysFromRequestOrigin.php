<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Laravel\Passkeys\Passkeys;
use Symfony\Component\HttpFoundation\Response;

class AllowPasskeysFromRequestOrigin
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (!self::isUnderRelyingPartyId($request->getHost())) {
            return $next($request);
        }

        $configuredOrigins = $this->config->get('passkeys.allowed_origins', []);

        $this->config->set('passkeys.allowed_origins', [...$configuredOrigins, $request->getSchemeAndHttpHost()]);

        try {
            return $next($request);
        } finally {
            $this->config->set('passkeys.allowed_origins', $configuredOrigins);
        }
    }

    private static function isUnderRelyingPartyId(string $host): bool
    {
        $relyingPartyId = Passkeys::relyingPartyId();

        return $host === $relyingPartyId || str_ends_with($host, ".$relyingPartyId");
    }
}
