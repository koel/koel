<?php

namespace App\Http\Integrations\LemonSqueezy\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

class ActivateLicenseRequest extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(private string $key)
    {
    }

    public function resolveEndpoint(): string
    {
        return '/licenses/activate';
    }

    /** @return array<mixed> */
    protected function defaultBody(): array
    {
        return [
            'license_key' => $this->key,
            'instance_name' => 'Koel Plus',
        ];
    }
}
