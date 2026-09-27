<?php

namespace App\Ai\Services;

use App\Models\Organization;
use App\Values\AiSettings;
use Laravel\Ai\AiManager;
use Laravel\Ai\Providers\Provider;

class AiProviderRegistrar
{
    public function __construct(
        private readonly AiManager $aiManager,
    ) {}

    /**
     * @return string the provider name to prompt with
     */
    public function registerOrganizationProvider(Organization $organization, AiSettings $settings): string
    {
        $name = "organization-$organization->id";
        $driver = $settings->provider->value;
        $apiKey = $settings->apiKey;

        $this->aiManager->forgetInstance($name);

        $this->aiManager->extend($name, fn (): Provider => $this->{'create' . ucfirst($driver) . 'Driver'}([
            'driver' => $driver,
            'name' => $name,
            'key' => $apiKey,
        ]));

        return $name;
    }
}
