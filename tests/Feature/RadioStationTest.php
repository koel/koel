<?php

namespace Tests\Feature;

use App\Http\Resources\RadioStationResource;
use App\Models\Organization;
use App\Models\RadioStation;
use App\Rules\ValidRadioStationUrl;
use App\Services\ImageStorage;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class RadioStationTest extends TestCase
{
    private ImageStorage|MockInterface $imageStorage;

    public function setUp(): void
    {
        parent::setUp();

        $this->imageStorage = $this->mock(ImageStorage::class);

        $validator = app(ValidRadioStationUrl::class);
        $validator->bypass = true;
    }

    #[Test]
    public function createStation(): void
    {
        $user = create_user();

        $this->imageStorage->expects('storeImage')
            ->with(minimal_base64_encoded_image())
            ->andReturn('logo.jpg');

        $this->postAs('/api/radio/stations', [
            'url' => 'https://example.com/stream',
            'name' => 'Test Radio Station',
            'logo' => minimal_base64_encoded_image(),
            'description' => 'A test radio station',
            'is_public' => true,
        ], $user)
            ->assertCreated()
            ->assertJsonStructure(RadioStationResource::JSON_STRUCTURE);

        $this->assertDatabaseHas(RadioStation::class, [
            'url' => 'https://example.com/stream',
            'name' => 'Test Radio Station',
            'logo' => 'logo.jpg',
            'description' => 'A test radio station',
            'is_public' => true,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function updateStation(): void
    {
        $this->imageStorage->shouldNotReceive('storeImage');

        /** @var RadioStation $station */
        $station = RadioStation::factory()->create();
        $logo = $station->getRawOriginal('logo');

        $this->putAs("/api/radio/stations/{$station->id}", [
            'url' => 'https://example.com/updated-stream',
            'name' => 'Updated Radio Station',
            'logo' => null,
            'description' => 'An updated test radio station',
            'is_public' => false,
        ], $station->user)
            ->assertOk()
            ->assertJsonStructure(RadioStationResource::JSON_STRUCTURE);

        $this->assertDatabaseHas(RadioStation::class, [
            'id' => $station->id,
            'url' => 'https://example.com/updated-stream',
            'name' => 'Updated Radio Station',
            'logo' => $logo, // logo should remain unchanged
            'description' => 'An updated test radio station',
            'is_public' => false,
        ]);
    }

    #[Test]
    public function updateStationWithNewLogo(): void
    {
        $this->imageStorage->expects('storeImage')
            ->with(minimal_base64_encoded_image())
            ->andReturn('new-logo.jpg');

        /** @var RadioStation $station */
        $station = RadioStation::factory()->create();

        $this->putAs("/api/radio/stations/{$station->id}", [
            'url' => 'https://example.com/updated-stream',
            'name' => 'Updated Radio Station',
            'logo' => minimal_base64_encoded_image(),
            'description' => 'An updated test radio station',
            'is_public' => true,
        ], $station->user)
            ->assertOk()
            ->assertJsonStructure(RadioStationResource::JSON_STRUCTURE);

        $this->assertDatabaseHas(RadioStation::class, [
            'id' => $station->id,
            'url' => 'https://example.com/updated-stream',
            'name' => 'Updated Radio Station',
            'logo' => 'new-logo.jpg', // logo should be updated
            'description' => 'An updated test radio station',
            'is_public' => true,
        ]);
    }

    #[Test]
    public function normalNonAdminCannotUpdate(): void
    {
        /** @var RadioStation $station */
        $station = RadioStation::factory()->create();
        $data = [
            'url' => 'https://example.com/updated-stream',
            'name' => 'Updated Radio Station',
            'logo' => null,
            'description' => 'An updated test radio station',
            'is_public' => false,
        ];

        $this->putAs("/api/radio/stations/{$station->id}", $data, create_user())
            ->assertForbidden();
    }

    #[Test]
    public function adminFromSameOrgCanUpdate(): void
    {
        /** @var RadioStation $station */
        $station = RadioStation::factory()->create();
        $data = [
            'url' => 'https://example.com/updated-stream',
            'name' => 'Updated Radio Station',
            'logo' => null,
            'description' => 'An updated test radio station',
            'is_public' => false,
        ];

        $this->putAs("/api/radio/stations/{$station->id}", $data, create_admin())
            ->assertOk();
    }

    #[Test]
    public function adminFromOtherOrgCannotUpdate(): void
    {
        /** @var RadioStation $station */
        $station = RadioStation::factory()->create();
        $data = [
            'url' => 'https://example.com/updated-stream',
            'name' => 'Updated Radio Station',
            'logo' => null,
            'description' => 'An updated test radio station',
            'is_public' => false,
        ];

        $this->putAs("/api/radio/stations/{$station->id}", $data, create_admin([
            'organization_id' => Organization::factory(),
        ]))
            ->assertForbidden();
    }

    #[Test]
    public function listAllStations(): void
    {
        $user = create_user();

        /** @var RadioStation $ownStation */
        $ownStation = RadioStation::factory()->for($user)->create();

        /** @var RadioStation $publicStation */
        $publicStation = RadioStation::factory()->create(['is_public' => true]);

        // Non-public station should not be included
        RadioStation::factory()->count(2)->create(['is_public' => false]);

        // Public station but in another organization should not be included
        RadioStation::factory()->create([
            'is_public' => true,
            'user_id' => create_user(['organization_id' => Organization::factory()])->id,
        ]);

        $this->getAs('/api/radio/stations', $user)
            ->assertOk()
            ->assertJsonStructure(['*' => RadioStationResource::JSON_STRUCTURE])
            ->assertJsonCount(2, '*')
            ->assertJsonFragment(['id' => $ownStation->id])
            ->assertJsonFragment(['id' => $publicStation->id]);
    }

    #[Test]
    public function deleteRadioStation(): void
    {
        /** @var RadioStation $station */
        $station = RadioStation::factory()->create();

        $this->deleteAs("/api/radio/stations/{$station->id}", [], $station->user)
            ->assertNoContent();

        $this->assertModelMissing($station);
    }

    #[Test]
    public function nonAdminCannotDelete(): void
    {
        /** @var RadioStation $station */
        $station = RadioStation::factory()->create();

        $this->deleteAs("/api/radio/stations/{$station->id}", [], create_user())
            ->assertForbidden();
    }

    #[Test]
    public function adminFromOtherOrgCannotDelete(): void
    {
        /** @var RadioStation $station */
        $station = RadioStation::factory()->create();

        $this->deleteAs("/api/radio/stations/{$station->id}", [], create_admin([
            'organization_id' => Organization::factory(),
        ]))
            ->assertForbidden();
    }

    #[Test]
    public function adminFromSameOrgCanDelete(): void
    {
        /** @var RadioStation $station */
        $station = RadioStation::factory()->create();

        $this->deleteAs("/api/radio/stations/{$station->id}", [], create_admin())
            ->assertNoContent();

        $this->assertModelMissing($station);
    }
}
