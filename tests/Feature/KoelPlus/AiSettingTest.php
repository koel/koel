<?php

namespace Tests\Feature\KoelPlus;

use App\Enums\AiProvider;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use App\Values\AiSettings;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;

class AiSettingTest extends PlusTestCase
{
    private static function configureTheServer(): void
    {
        config(['koel.ai.enabled' => true, 'ai.default' => 'ollama']);
    }

    private function updateAiSettings(User $user, array $data): TestResponse
    {
        return $this->putAs('api/settings/ai', $data, $user);
    }

    private static function getAiSettings(Organization $organization): AiSettings
    {
        return app(SettingService::class)->getAiSettings($organization);
    }

    #[Test]
    public function turnOnTheAssistantWithAKey(): void
    {
        $admin = create_admin();

        $this
            ->updateAiSettings($admin, ['enabled' => true, 'provider' => 'anthropic', 'api_key' => 'sk-ant-test'])
            ->assertOk()
            ->assertExactJson([
                'source' => 'organization',
                'enabled' => true,
                'provider' => 'anthropic',
                'has_api_key' => true,
            ]);

        $settings = self::getAiSettings($admin->organization);

        self::assertTrue($settings->enabled);
        self::assertSame('anthropic', $settings->provider);
        self::assertSame('sk-ant-test', $settings->apiKey);
    }

    #[Test]
    public function storeTheKeyEncrypted(): void
    {
        $admin = create_admin();

        $this->updateAiSettings($admin, [
            'enabled' => true,
            'provider' => 'openai',
            'api_key' => 'sk-test',
        ])->assertOk();

        self::assertStringNotContainsString('sk-test', json_encode(Setting::get('ai', $admin->organization)));
    }

    #[Test]
    public function keepTheStoredKeyWhenNoneIsSent(): void
    {
        $admin = create_admin();
        app(SettingService::class)->updateAiSettings($admin->organization, true, AiProvider::OpenAi, 'sk-test');

        $this->updateAiSettings($admin, ['enabled' => false, 'provider' => 'openai'])->assertOk();

        $settings = self::getAiSettings($admin->organization);

        self::assertFalse($settings->enabled);
        self::assertSame('sk-test', $settings->apiKey);
    }

    #[Test]
    public function requireAKeyToTurnTheAssistantOnForTheFirstTime(): void
    {
        $this->updateAiSettings(create_admin(), [
            'enabled' => true,
            'provider' => 'openai',
        ])->assertJsonValidationErrors('api_key');
    }

    #[Test]
    public function requireANewKeyWhenTheProviderChanges(): void
    {
        $admin = create_admin();
        app(SettingService::class)->updateAiSettings($admin->organization, true, AiProvider::OpenAi, 'sk-test');

        $this->updateAiSettings($admin, ['enabled' => true, 'provider' => 'anthropic'])->assertJsonValidationErrors(
            'api_key',
        );
    }

    #[Test]
    public function dropTheStoredKeyWhenTheProviderChangesWithoutANewOne(): void
    {
        $admin = create_admin();
        app(SettingService::class)->updateAiSettings($admin->organization, true, AiProvider::OpenAi, 'sk-test');

        $this->updateAiSettings($admin, ['enabled' => false, 'provider' => 'anthropic'])->assertOk();

        $settings = self::getAiSettings($admin->organization);

        self::assertSame('anthropic', $settings->provider);
        self::assertNull($settings->apiKey);
    }

    #[Test]
    public function treatAKeyThatCannotBeDecryptedAsMissingWithoutFallingBackToTheServer(): void
    {
        self::configureTheServer();
        $admin = create_admin();
        Setting::set(
            'ai',
            ['enabled' => true, 'provider' => 'openai', 'api_key' => 'not-encrypted'],
            $admin->organization,
        );

        $this->getAs('api/data', $admin)->assertOk()->assertJsonPath('uses_ai', false);

        self::assertNull(self::getAiSettings($admin->organization)->apiKey);
    }

    #[Test]
    public function fallBackToTheServerWhenTheOrganizationHasNoSetting(): void
    {
        self::configureTheServer();
        $admin = create_admin();

        $this
            ->getAs('api/data', $admin)
            ->assertJsonPath('uses_ai', true)
            ->assertJsonPath('settings.ai', [
                'source' => 'environment',
                'enabled' => true,
                'provider' => 'ollama',
                'has_api_key' => false,
            ]);
    }

    #[Test]
    public function letAnOrganizationSettingTurnOffTheServerConfiguration(): void
    {
        self::configureTheServer();
        $admin = create_admin();
        app(SettingService::class)->updateAiSettings($admin->organization, false, AiProvider::OpenAi, 'sk-test');

        $this->getAs('api/data', $admin)->assertJsonPath('uses_ai', false);
    }

    #[Test]
    public function requireAKeyWhenSwitchingFromTheServerConfiguration(): void
    {
        config([
            'koel.ai.enabled' => true,
            'ai.default' => 'openai',
            'ai.providers.openai.key' => 'sk-server',
        ]);

        $this->updateAiSettings(create_admin(), [
            'enabled' => true,
            'provider' => 'openai',
        ])->assertJsonValidationErrors('api_key');
    }

    #[Test]
    public function refuseAProviderThatIsNotOffered(): void
    {
        $this->updateAiSettings(create_admin(), [
            'enabled' => true,
            'provider' => 'ollama',
            'api_key' => 'x',
        ])->assertJsonValidationErrors('provider');
    }

    #[Test]
    public function refuseAUserWhoCannotManageSettings(): void
    {
        $this->updateAiSettings(create_user(), [
            'enabled' => true,
            'provider' => 'openai',
            'api_key' => 'sk-test',
        ])->assertForbidden();
    }

    #[Test]
    public function tellAnAdminTheSettingsWithoutTheKey(): void
    {
        $admin = create_admin();
        app(SettingService::class)->updateAiSettings($admin->organization, true, AiProvider::Gemini, 'gm-test');

        $this
            ->getAs('api/data', $admin)
            ->assertJsonPath('settings.ai', [
                'source' => 'organization',
                'enabled' => true,
                'provider' => 'gemini',
                'has_api_key' => true,
            ])
            ->assertJsonPath('uses_ai', true);
    }

    #[Test]
    public function offerTheAssistantOnlyToOrganizationsThatTurnedItOn(): void
    {
        $admin = create_admin();
        app(SettingService::class)->updateAiSettings($admin->organization, true, AiProvider::OpenAi, 'sk-test');

        $outsider = User::factory()->for(Organization::factory())->createOne();

        $this->getAs('api/data', $outsider)->assertJsonPath('uses_ai', false);
    }
}
