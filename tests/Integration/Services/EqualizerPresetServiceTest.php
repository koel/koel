<?php

namespace Tests\Integration\Services;

use App\Services\EqualizerPresetService;
use App\Values\EqualizerPreset;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class EqualizerPresetServiceTest extends TestCase
{
    private EqualizerPresetService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(EqualizerPresetService::class);
    }

    #[Test]
    public function addingAPresetKeepsPresetsSavedAfterTheUserWasLoaded(): void
    {
        $user = create_user();
        $sameUserLoadedEarlier = $user->fresh();

        $this->service->addPresetForUser($user, EqualizerPreset::make('Bass', 0, array_fill(0, 10, 0)));
        $this->service->addPresetForUser($sameUserLoadedEarlier, EqualizerPreset::make(
            'Treble',
            0,
            array_fill(0, 10, 0),
        ));

        self::assertSame(['Bass', 'Treble'], $user->refresh()->preferences->equalizerPresets->pluck('name')->all());
    }

    #[Test]
    public function removingAPresetKeepsPresetsSavedAfterTheUserWasLoaded(): void
    {
        $user = create_user();
        $bass = $this->service->addPresetForUser($user, EqualizerPreset::make('Bass', 0, array_fill(0, 10, 0)));
        $sameUserLoadedEarlier = $user->fresh();

        $this->service->addPresetForUser($user, EqualizerPreset::make('Treble', 0, array_fill(0, 10, 0)));
        $this->service->removePresetForUser($sameUserLoadedEarlier, $bass->id);

        self::assertSame(['Treble'], $user->refresh()->preferences->equalizerPresets->pluck('name')->all());
    }
}
