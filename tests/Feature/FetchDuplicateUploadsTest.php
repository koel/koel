<?php

namespace Tests\Feature;

use App\Http\Resources\DuplicateUploadResource;
use App\Models\DuplicateUpload;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class FetchDuplicateUploadsTest extends TestCase
{
    #[Test]
    public function unauthenticatedUserCannotFetchDuplicateUploads(): void
    {
        $this->json('get', 'api/duplicate-uploads')->assertUnauthorized();
    }

    #[Test]
    public function fetchDuplicateUploadsForCurrentUser(): void
    {
        $user = create_user();
        DuplicateUpload::factory()
            ->count(3)
            ->for($user)
            ->create();

        $this
            ->getAs('api/duplicate-uploads', $user)
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [DuplicateUploadResource::JSON_STRUCTURE]]);
    }

    #[Test]
    public function onlyCurrentUsersUploadsAreReturned(): void
    {
        $user = create_user();
        $otherUser = create_user();

        DuplicateUpload::factory()
            ->count(2)
            ->for($user)
            ->create();
        DuplicateUpload::factory()
            ->count(3)
            ->for($otherUser)
            ->create();

        $this->getAs('api/duplicate-uploads', $user)->assertSuccessful()->assertJsonCount(2, 'data');
    }

    #[Test]
    public function returnsEmptyWhenNoDuplicatesExist(): void
    {
        $user = create_user();

        $this->getAs('api/duplicate-uploads', $user)->assertSuccessful()->assertJsonCount(0, 'data');
    }

    #[Test]
    public function responseIncludesFilename(): void
    {
        $user = create_user();
        DuplicateUpload::factory()->for($user)->create(['location' => '/var/media/koel/my-song.mp3']);

        $this
            ->getAs('api/duplicate-uploads', $user)
            ->assertSuccessful()
            ->assertJsonPath('data.0.filename', 'my-song.mp3');
    }

    #[Test]
    public function responseIsPaginated(): void
    {
        $user = create_user();
        DuplicateUpload::factory()
            ->count(5)
            ->for($user)
            ->create();

        $this
            ->getAs('api/duplicate-uploads?per_page=2', $user)
            ->assertSuccessful()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data', 'links' => ['next', 'prev']]);
    }
}
