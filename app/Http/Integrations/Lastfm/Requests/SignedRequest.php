<?php

namespace App\Http\Integrations\Lastfm\Requests;

use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Repositories\Body\FormBodyRepository;

abstract class SignedRequest extends Request
{
    public function __construct()
    {
        $this->middleware()->onRequest(fn (PendingRequest $request) => $this->sign($request));
    }

    protected function sign(PendingRequest $request): void
    {
        if ($request->body() instanceof FormBodyRepository) {
            $request->body()->add('api_sig', self::createSignature($request->body()->all()));

            return;
        }

        $request->query()->add('api_sig', self::createSignature($request->query()->all()));
    }

    private static function createSignature(array $parameters): string
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

        $str .= config('koel.lastfm.secret');

        return md5($str);
    }
}
