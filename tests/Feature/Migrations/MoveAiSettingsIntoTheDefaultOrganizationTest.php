<?php

namespace Tests\Feature\Migrations;

use App\Models\Organization;
use App\Models\Setting;
use App\Services\SettingService;
use Laravel\Ai\Enums\Lab;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MoveAiSettingsIntoTheDefaultOrganizationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Organization::default();
    }

    private static function runMigration(): void
    {
        $migration = require
            database_path('migrations/2026_09_27_100100_move_ai_settings_into_the_default_organization.php');

        $migration->up();
    }

    #[Test]
    public function moveTheEnvironmentSettingsIntoTheDefaultOrganization(): void
    {
        config([
            'koel.ai.enabled' => true,
            'ai.default' => 'anthropic',
            'ai.providers.anthropic.key' => 'sk-ant-test',
        ]);

        self::runMigration();

        $settings = app(SettingService::class)->getAiSettings(Organization::default());

        self::assertTrue($settings->enabled);
        self::assertSame(Lab::Anthropic, $settings->provider);
        self::assertSame('sk-ant-test', $settings->apiKey);
    }

    #[Test]
    public function moveAKeyThatWasSwitchedOffAsSwitchedOff(): void
    {
        config([
            'koel.ai.enabled' => false,
            'ai.default' => 'openai',
            'ai.providers.openai.key' => 'sk-test',
        ]);

        self::runMigration();

        self::assertFalse(app(SettingService::class)->getAiSettings(Organization::default())->enabled);
    }

    #[Test]
    public function moveNothingWithoutAKey(): void
    {
        config(['koel.ai.enabled' => true, 'ai.default' => 'openai', 'ai.providers.openai.key' => null]);

        self::runMigration();

        self::assertNull(Setting::get('ai', Organization::default()));
    }

    #[Test]
    public function moveNothingForAProviderThatIsNotOffered(): void
    {
        config(['koel.ai.enabled' => true, 'ai.default' => 'ollama', 'ai.providers.ollama.key' => 'x']);

        self::runMigration();

        self::assertNull(Setting::get('ai', Organization::default()));
    }
}
