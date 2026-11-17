<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Services\Integrations\YouTubeService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class YouTubeSettingTest extends TestCase
{
    #[Test]
    public function saveTheApiKey(): void
    {
        $this
            ->putAs('api/settings/youtube', ['api_key' => 'stored-key'], create_admin())
            ->assertOk()
            ->assertExactJson(['has_api_key' => true, 'enabled' => true]);

        self::assertSame('stored-key', app(YouTubeService::class)->getApiKey(Organization::default()));
    }

    #[Test]
    public function removeTheApiKey(): void
    {
        config(['koel.services.youtube.key' => null]);
        app(YouTubeService::class)->updateApiKey(Organization::default(), 'stored-key');

        $this
            ->putAs('api/settings/youtube', ['api_key' => null], create_admin())
            ->assertOk()
            ->assertExactJson(['has_api_key' => false, 'enabled' => false]);
    }

    #[Test]
    public function nonAdminCannotSaveTheApiKey(): void
    {
        $this->putAs('api/settings/youtube', ['api_key' => 'stored-key'])->assertForbidden();
    }

    #[Test]
    public function tellAdminsWhetherAnApiKeyIsStored(): void
    {
        app(YouTubeService::class)->updateApiKey(Organization::default(), 'stored-key');

        $this->getAs('api/data', create_admin())->assertJsonPath('settings.youtube.has_api_key', true);
    }
}
