<?php

namespace Tests\Feature;

use App\Ai\Agents\KoelAssistant;
use App\Services\SettingService;
use Laravel\Ai\Enums\Lab;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class AiSettingTest extends TestCase
{
    #[Test]
    public function notAccessibleInCommunityLicense(): void
    {
        $this->putAs(
            'api/settings/ai',
            ['enabled' => true, 'provider' => 'openai', 'api_key' => 'sk-test'],
            create_admin(),
        )->assertNotFound();
    }

    #[Test]
    public function keepTheAssistantOffInCommunityLicenseEvenWithAKey(): void
    {
        KoelAssistant::fake();

        $admin = create_admin();
        app(SettingService::class)->updateAiSettings($admin->organization, true, Lab::OpenAI, 'sk-test');

        $this->postAs('api/ai/prompt', ['prompt' => 'Play some jazz'], $admin)->assertNotFound();

        $this->getAs('api/data', $admin)->assertJsonPath('uses_ai', false)->assertJsonMissingPath('settings.ai');

        KoelAssistant::assertNeverPrompted();
    }
}
