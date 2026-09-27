<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use App\Values\AiSettings;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Enums\Lab;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_manager;
use function Tests\create_user;

class AiSettingTest extends PlusTestCase
{
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
            ->assertExactJson(['enabled' => true, 'provider' => 'anthropic', 'has_api_key' => true]);

        $settings = self::getAiSettings($admin->organization);

        self::assertTrue($settings->enabled);
        self::assertSame(Lab::Anthropic, $settings->provider);
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
        app(SettingService::class)->updateAiSettings($admin->organization, true, Lab::OpenAI, 'sk-test');

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
        app(SettingService::class)->updateAiSettings($admin->organization, true, Lab::OpenAI, 'sk-test');

        $this->updateAiSettings($admin, ['enabled' => true, 'provider' => 'anthropic'])->assertJsonValidationErrors(
            'api_key',
        );
    }

    #[Test]
    public function dropTheStoredKeyWhenTheProviderChangesWithoutANewOne(): void
    {
        $admin = create_admin();
        app(SettingService::class)->updateAiSettings($admin->organization, true, Lab::OpenAI, 'sk-test');

        $this->updateAiSettings($admin, ['enabled' => false, 'provider' => 'anthropic'])->assertOk();

        $settings = self::getAiSettings($admin->organization);

        self::assertSame(Lab::Anthropic, $settings->provider);
        self::assertNull($settings->apiKey);
    }

    #[Test]
    public function treatAKeyThatCannotBeDecryptedAsMissing(): void
    {
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
    #[TestWith(['deepseek'])]
    #[TestWith(['groq'])]
    #[TestWith(['mistral'])]
    #[TestWith(['openrouter'])]
    #[TestWith(['xai'])]
    public function acceptEveryProviderThatNeedsOnlyAKey(string $provider): void
    {
        $this
            ->updateAiSettings(create_admin(), ['enabled' => true, 'provider' => $provider, 'api_key' => 'k-test'])
            ->assertOk()
            ->assertJsonPath('provider', $provider);
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
    public function refuseAManager(): void
    {
        $this->updateAiSettings(create_manager(), [
            'enabled' => true,
            'provider' => 'openai',
            'api_key' => 'sk-test',
        ])->assertForbidden();
    }

    #[Test]
    public function keepTheSettingsFromAnyoneButAnAdmin(): void
    {
        $manager = create_manager();
        app(SettingService::class)->updateAiSettings($manager->organization, true, Lab::OpenAI, 'sk-test');

        $this->getAs('api/data', $manager)->assertJsonMissingPath('settings.ai');
        $this->getAs('api/data', create_user())->assertJsonMissingPath('settings.ai');
    }

    #[Test]
    public function tellAnAdminTheSettingsWithoutTheKey(): void
    {
        $admin = create_admin();
        app(SettingService::class)->updateAiSettings($admin->organization, true, Lab::Gemini, 'gm-test');

        $this
            ->getAs('api/data', $admin)
            ->assertJsonPath('settings.ai', ['enabled' => true, 'provider' => 'gemini', 'has_api_key' => true])
            ->assertJsonPath('uses_ai', true);
    }

    #[Test]
    public function offerTheAssistantOnlyToOrganizationsThatTurnedItOn(): void
    {
        $admin = create_admin();
        app(SettingService::class)->updateAiSettings($admin->organization, true, Lab::OpenAI, 'sk-test');

        $outsider = User::factory()->for(Organization::factory())->createOne();

        $this->getAs('api/data', $outsider)->assertJsonPath('uses_ai', false);
    }
}
