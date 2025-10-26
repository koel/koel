<?php

namespace Tests\Feature;

use App\Helpers\Ulid;
use App\Http\Resources\RadioStationResource;
use App\Models\Organization;
use App\Models\RadioStation;
use App\Rules\ValidRadioStationUrl;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class RadioStationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $validator = app(ValidRadioStationUrl::class);
        $validator->bypass = true;
    }

    #[Test]
    public function create(): void
    {
        $user = create_user();

        $ulid = Ulid::freeze();

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
            'logo' => "$ulid.webp",
            'description' => 'A test radio station',
            'is_public' => true,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function updateKeepingLogoIntact(): void
    {
        /** @var RadioStation $station */
        $station = RadioStation::factory()->create([
            'logo' => 'neat-logo.webp',
        ]);

        $this->putAs("/api/radio/stations/{$station->id}", [
            'url' => 'https://example.com/updated-stream',
            'name' => 'Updated Radio Station',
            'description' => 'An updated test radio station',
            'is_public' => false,
        ], $station->user)
            ->assertOk()
            ->assertJsonStructure(RadioStationResource::JSON_STRUCTURE);

        $station->refresh();

        self::assertEquals('neat-logo.webp', $station->logo);
        self::assertEquals('https://example.com/updated-stream', $station->url);
        self::assertEquals('Updated Radio Station', $station->name);
        self::assertEquals('An updated test radio station', $station->description);
        self::assertFalse($station->is_public);
    }

    #[Test]
    public function updateWithNewLogo(): void
    {
        /** @var RadioStation $station */
        $station = RadioStation::factory()->create();

        $ulid = Ulid::freeze();

        $this->putAs("/api/radio/stations/{$station->id}", [
            'url' => 'https://example.com/updated-stream',
            'name' => 'Updated Radio Station',
            'logo' => minimal_base64_encoded_image(),
            'is_public' => true,
        ], $station->user)
            ->assertOk()
            ->assertJsonStructure(RadioStationResource::JSON_STRUCTURE);

        self::assertSame("$ulid.webp", $station->refresh()->logo);
    }

    #[Test]
    public function updateRemovingLogo(): void
    {
        /** @var RadioStation $station */
        $station = RadioStation::factory()->create();

        $this->putAs("/api/radio/stations/{$station->id}", [
            'url' => 'https://example.com/updated-stream',
            'name' => 'Updated Radio Station',
            'logo' => '',
            'is_public' => true,
        ], $station->user)
            ->assertOk()
            ->assertJsonStructure(RadioStationResource::JSON_STRUCTURE);

        self::assertEmpty($station->refresh()->logo);
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
    public function listAll(): void
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
    public function destroy(): void
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
