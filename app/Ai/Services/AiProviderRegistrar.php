<?php

namespace App\Ai\Services;

use App\Models\Organization;
use App\Values\AiSettings;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Arr;
use Laravel\Ai\AiManager;
use Laravel\Ai\Providers\Provider;

class AiProviderRegistrar
{
    /**
     * @param array<string, array<string, mixed>> $providers
     */
    public function __construct(
        private readonly AiManager $aiManager,
        #[Config('ai.providers')]
        private readonly array $providers = [],
    ) {}

    /**
     * @return string the provider name to prompt with
     */
    public function registerOrganizationProvider(Organization $organization, AiSettings $settings): string
    {
        $name = "organization-$organization->id";
        $driver = $settings->provider->value;
        $apiKey = $settings->apiKey;
        $configured = Arr::get($this->providers, $driver, []);

        $this->aiManager->forgetInstance($name);

        $this->aiManager->extend($name, fn (): Provider => $this->{'create' . ucfirst($driver) . 'Driver'}([
            ...$configured,
            'driver' => $driver,
            'name' => $name,
            'key' => $apiKey,
        ]));

        return $name;
    }
}
