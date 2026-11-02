<?php

namespace Tests\Feature;

use App\Values\User\UserPreferences;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeExtensionPreference;
use Tests\TestCase;

use function Tests\create_user;

class UpdateUserPreferenceTest extends TestCase
{
    protected function tearDown(): void
    {
        UserPreferences::forgetRegisteredPreferences();

        parent::tearDown();
    }

    #[Test]
    public function saveARegisteredPreference(): void
    {
        UserPreferences::register(FakeExtensionPreference::class);
        $user = create_user();

        $this->patchAs('api/me/preferences', ['key' => 'fake_extension', 'value' => false], $user)->assertNoContent();

        self::assertFalse($user->refresh()->preferences->toArray()['fake_extension']);
    }

    #[Test]
    public function refuseToSaveAnUnknownPreference(): void
    {
        $user = create_user();

        $this->patchAs(
            'api/me/preferences',
            ['key' => 'fake_extension', 'value' => false],
            $user,
        )->assertUnprocessable();
    }

    #[Test]
    public function saveACustomizablePreference(): void
    {
        $user = create_user();

        $this->patchAs('api/me/preferences', ['key' => 'volume', 'value' => 3], $user)->assertNoContent();

        self::assertSame(3.0, $user->refresh()->preferences->volume);
    }

    #[Test]
    public function refuseToSaveEqualizerPresetsOutsideTheirOwnEndpoints(): void
    {
        $user = create_user();

        $this->patchAs(
            'api/me/preferences',
            [
                'key' => 'equalizer_presets',
                'value' => [['name' => 'Bass', 'preamp' => 0, 'gains' => array_fill(0, 10, 0)]],
            ],
            $user,
        )->assertUnprocessable();

        self::assertCount(0, $user->refresh()->preferences->equalizerPresets);
    }
}
