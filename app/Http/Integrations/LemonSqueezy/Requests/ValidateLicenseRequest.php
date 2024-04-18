<?php

namespace App\Http\Integrations\LemonSqueezy\Requests;

use App\Models\License;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

class ValidateLicenseRequest extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(private readonly License $license)
    {
    }

    public function resolveEndpoint(): string
    {
        return '/licenses/validate';
    }

    /** @return array<mixed> */
    protected function defaultBody(): array
    {
        return [
            'license_key' => $this->license->key,
            'instance_id' => $this->license->instance->id,
        ];
    }
}
