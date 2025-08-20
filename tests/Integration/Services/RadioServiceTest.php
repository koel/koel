<?php

namespace Tests\Integration\Services;

use App\Models\RadioStation;
use App\Services\RadioService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class RadioServiceTest extends TestCase
{
    private RadioService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(RadioService::class);
    }

    #[Test]
    public function createRadioStation(): void
    {
        $user = create_user();

        $station = $this->service->createRadioStation(
            url: 'https://example.com/stream',
            name: 'Test Radio',
            logo: null,
            description: 'A test radio station',
            isPublic: true,
            user: $user,
        );

        self::assertSame('Test Radio', $station->name);
        self::assertSame('https://example.com/stream', $station->url);
        self::assertTrue($station->is_public);
        self::assertSame('A test radio station', $station->description);
        self::assertSame($user->id, $station->user_id);
        self::assertNull($station->logo);
    }

    #[Test]
    public function updateRadioStation(): void
    {
        /** @var RadioStation $station */
        $station = RadioStation::factory()->create();

        $updatedStation = $this->service->updateRadioStation(
            radioStation: $station,
            url: 'https://example.com/new-stream',
            name: 'Updated Radio',
            logo: null,
            description: 'An updated test radio station',
            isPublic: false,
        );

        self::assertSame('Updated Radio', $updatedStation->name);
        self::assertSame('https://example.com/new-stream', $updatedStation->url);
        self::assertFalse($updatedStation->is_public);
        self::assertSame('An updated test radio station', $updatedStation->description);
    }

    #[Test]
    public function removeRadioStationLogo(): void
    {
        /** @var RadioStation $station */
        $station = RadioStation::factory()->create([
            'logo' => 'path/to/logo.png',
        ]);

        $this->service->removeStationLogo($station);
        self::assertNull($station->logo);
    }
}
