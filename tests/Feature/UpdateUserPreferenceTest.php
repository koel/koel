<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class UpdateUserPreferenceTest extends TestCase
{
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
