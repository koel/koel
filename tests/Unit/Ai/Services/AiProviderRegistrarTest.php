<?php

namespace Tests\Unit\Ai\Services;

use App\Ai\Services\AiProviderRegistrar;
use App\Models\Organization;
use App\Values\AiSettings;
use Laravel\Ai\AiManager;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Providers\Provider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiProviderRegistrarTest extends TestCase
{
    private static function resolveProvider(string $name): Provider
    {
        $provider = app(AiManager::class)->textProvider($name);
        self::assertInstanceOf(Provider::class, $provider);

        return $provider;
    }

    #[Test]
    public function registerAProviderWithTheOrganizationKey(): void
    {
        $organization = Organization::factory()->createOne();

        $name = app(AiProviderRegistrar::class)->registerOrganizationProvider($organization, AiSettings::make(
            enabled: true,
            provider: Lab::Anthropic,
            apiKey: 'sk-ant-test',
        ));

        $provider = self::resolveProvider($name);

        self::assertSame('anthropic', $provider->driver());
        self::assertSame('sk-ant-test', $provider->providerCredentials()['key']);
    }

    #[Test]
    public function replaceTheProviderWhenTheKeyChanges(): void
    {
        $organization = Organization::factory()->createOne();
        $registrar = app(AiProviderRegistrar::class);

        $registrar->registerOrganizationProvider($organization, AiSettings::make(
            enabled: true,
            provider: Lab::OpenAI,
            apiKey: 'sk-old',
        ));
        self::resolveProvider("organization-$organization->id");

        $name = $registrar->registerOrganizationProvider($organization, AiSettings::make(
            enabled: true,
            provider: Lab::OpenAI,
            apiKey: 'sk-new',
        ));

        self::assertSame('sk-new', self::resolveProvider($name)->providerCredentials()['key']);
    }

    #[Test]
    public function keepTheRestOfTheProviderConfiguration(): void
    {
        config(['ai.providers.openai.url' => 'https://proxy.example.com/v1']);
        $organization = Organization::factory()->createOne();

        $name = app(AiProviderRegistrar::class)->registerOrganizationProvider($organization, AiSettings::make(
            enabled: true,
            provider: Lab::OpenAI,
            apiKey: 'sk-test',
        ));

        $provider = self::resolveProvider($name);

        self::assertSame('https://proxy.example.com/v1', $provider->additionalConfiguration()['url']);
        self::assertSame('sk-test', $provider->providerCredentials()['key']);
    }
}
