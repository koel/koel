<?php

namespace App\Http\Integrations\Lastfm\Auth;

use App\Http\Integrations\Lastfm\Contracts\RequiresSignature;
use Saloon\Contracts\Authenticator;
use Saloon\Http\PendingRequest;
use Saloon\Repositories\Body\FormBodyRepository;

final class LastfmAuthenticator implements Authenticator
{
    public function __construct(private string $key, private string $secret)
    {
    }

    public function set(PendingRequest $pendingRequest): void
    {
        $this->addApiKey($pendingRequest);

        if ($pendingRequest->getRequest() instanceof RequiresSignature) {
            $this->sign($pendingRequest);
        }
    }

    private function addApiKey(PendingRequest $request): void
    {
        if ($request->body() instanceof FormBodyRepository) {
            $request->body()->add('api_key', $this->key);
        } else {
            $request->query()->add('api_key', $this->key);
        }
    }

    protected function sign(PendingRequest $request): void
    {
        if ($request->body() instanceof FormBodyRepository) {
            $request->body()->add('api_sig', $this->createSignature($request->body()->all()));
        } else {
            $request->query()->add('api_sig', $this->createSignature($request->query()->all()));
        }
    }

    private function createSignature(array $parameters): string
    {
        ksort($parameters);

        // Generate the API signature.
        // @link http://www.last.fm/api/webauth#6
        $str = '';

        foreach ($parameters as $name => $value) {
            if ($name === 'format') {
                // The format parameter is not part of the signature.
                continue;
            }

            $str .= $name . $value;
        }

        $str .= $this->secret;

        return md5($str);
    }
}
