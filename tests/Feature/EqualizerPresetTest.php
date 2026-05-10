<?php

namespace Tests\Feature;

use App\Values\EqualizerPreset;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class EqualizerPresetTest extends TestCase
{
    private const array VALID_GAINS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

    #[Test]
    public function storeRequiresAuthentication(): void
    {
        $this->postJson('api/me/equalizer-presets', [
            'name' => 'My Bass',
            'preamp' => 0,
            'gains' => self::VALID_GAINS,
        ])->assertUnauthorized();
    }

    #[Test]
    public function storeReturnsPresetWithServerMintedUlid(): void
    {
        $user = create_user();

        $response = $this
            ->postAs(
                'api/me/equalizer-presets',
                [
                    'name' => 'My Bass',
                    'preamp' => 1.5,
                    'gains' => self::VALID_GAINS,
                ],
                $user,
            )
            ->assertOk()
            ->json();

        self::assertNotEmpty($response['id']);
        self::assertSame('My Bass', $response['name']);
        self::assertSame(1.5, $response['preamp']);
        self::assertSame(array_map('floatval', self::VALID_GAINS), array_map('floatval', $response['gains']));

        $user->refresh();
        self::assertCount(1, $user->preferences->equalizerPresets);

        /** @var EqualizerPreset $stored */
        $stored = $user->preferences->equalizerPresets->first();
        self::assertSame($response['id'], $stored->id);
        self::assertSame('My Bass', $stored->name);
    }

    #[Test]
    public function storeAppendsToExistingPresets(): void
    {
        $user = create_user();

        $this->postAs(
            'api/me/equalizer-presets',
            ['name' => 'First', 'preamp' => 0, 'gains' => self::VALID_GAINS],
            $user,
        )->assertOk();

        $this->postAs(
            'api/me/equalizer-presets',
            ['name' => 'Second', 'preamp' => 0, 'gains' => self::VALID_GAINS],
            $user,
        )->assertOk();

        $user->refresh();

        self::assertSame(['First', 'Second'], $user->preferences->equalizerPresets->pluck('name')->all());
    }

    #[Test]
    public function storeKeepsPresetsSortedAlphabeticallyByName(): void
    {
        $user = create_user();

        foreach (['Zeta', 'alpha', 'Mu'] as $name) {
            $this->postAs(
                'api/me/equalizer-presets',
                ['name' => $name, 'preamp' => 0, 'gains' => self::VALID_GAINS],
                $user,
            )->assertOk();
        }

        $user->refresh();

        self::assertSame(['alpha', 'Mu', 'Zeta'], $user->preferences->equalizerPresets->pluck('name')->all());
    }

    #[Test]
    public function storeMintsAUniqueIdPerPreset(): void
    {
        $user = create_user();

        $first = $this->postAs(
            'api/me/equalizer-presets',
            ['name' => 'A', 'preamp' => 0, 'gains' => self::VALID_GAINS],
            $user,
        )->json('id');

        $second = $this->postAs(
            'api/me/equalizer-presets',
            ['name' => 'B', 'preamp' => 0, 'gains' => self::VALID_GAINS],
            $user,
        )->json('id');

        self::assertNotSame($first, $second);
    }

    #[Test]
    public function storeRejectsBlankName(): void
    {
        $user = create_user();

        $this->postAs(
            'api/me/equalizer-presets',
            ['name' => '', 'preamp' => 0, 'gains' => self::VALID_GAINS],
            $user,
        )->assertUnprocessable();
    }

    #[Test]
    public function storeRejectsGainsOfWrongLength(): void
    {
        $user = create_user();

        $this->postAs(
            'api/me/equalizer-presets',
            ['name' => 'X', 'preamp' => 0, 'gains' => [1, 2, 3]],
            $user,
        )->assertUnprocessable();
    }

    #[Test]
    public function storeRejectsOutOfRangePreampOrGain(): void
    {
        $user = create_user();

        $this->postAs(
            'api/me/equalizer-presets',
            ['name' => 'X', 'preamp' => 50, 'gains' => self::VALID_GAINS],
            $user,
        )->assertUnprocessable();

        $this->postAs(
            'api/me/equalizer-presets',
            ['name' => 'X', 'preamp' => 0, 'gains' => [50, 0, 0, 0, 0, 0, 0, 0, 0, 0]],
            $user,
        )->assertUnprocessable();
    }

    #[Test]
    public function destroyRemovesTheMatchingPreset(): void
    {
        $user = create_user();

        $keep = $this->postAs(
            'api/me/equalizer-presets',
            ['name' => 'Keep', 'preamp' => 0, 'gains' => self::VALID_GAINS],
            $user,
        )->json('id');

        $drop = $this->postAs(
            'api/me/equalizer-presets',
            ['name' => 'Drop', 'preamp' => 0, 'gains' => self::VALID_GAINS],
            $user,
        )->json('id');

        $this->deleteAs("api/me/equalizer-presets/{$drop}", [], $user)->assertNoContent();

        $user->refresh();
        $remaining = $user->preferences->equalizerPresets->pluck('id')->all();

        self::assertSame([$keep], $remaining);
    }

    #[Test]
    public function destroyIsANoOpForUnknownIds(): void
    {
        $user = create_user();

        $this->postAs(
            'api/me/equalizer-presets',
            ['name' => 'Keep', 'preamp' => 0, 'gains' => self::VALID_GAINS],
            $user,
        )->assertOk();

        $this->deleteAs('api/me/equalizer-presets/01HUNKNOWN0000000000000000', [], $user)->assertNoContent();

        $user->refresh();
        self::assertCount(1, $user->preferences->equalizerPresets);
    }

    #[Test]
    public function destroyRequiresAuthentication(): void
    {
        $this->deleteJson('api/me/equalizer-presets/01HSOMEPRESETID000000000')->assertUnauthorized();
    }
}
