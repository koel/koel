<?php

namespace Tests\Feature;

use App\Models\DuplicateUpload;
use App\Models\Setting;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class DuplicateUploadActionTest extends TestCase
{
    #[Test]
    public function unauthenticatedUserCannotKeepDuplicate(): void
    {
        $upload = DuplicateUpload::factory()->createOne();

        $this->json('post', "api/duplicate-uploads/{$upload->id}")->assertUnauthorized();
    }

    #[Test]
    public function unauthenticatedUserCannotKeepAllDuplicates(): void
    {
        $this->json('post', 'api/duplicate-uploads')->assertUnauthorized();
    }

    #[Test]
    public function unauthenticatedUserCannotDiscardDuplicate(): void
    {
        $upload = DuplicateUpload::factory()->createOne();

        $this->json('delete', "api/duplicate-uploads/{$upload->id}")->assertUnauthorized();
    }

    #[Test]
    public function unauthenticatedUserCannotDiscardAllDuplicates(): void
    {
        $this->json('delete', 'api/duplicate-uploads')->assertUnauthorized();
    }

    #[Test]
    public function cannotKeepOtherUsersDuplicate(): void
    {
        $owner = create_user();
        $other = create_user();
        $upload = DuplicateUpload::factory()->for($owner)->createOne();

        $this->postAs("api/duplicate-uploads/{$upload->id}", [], $other)->assertForbidden();
    }

    #[Test]
    public function cannotDiscardOtherUsersDuplicate(): void
    {
        $owner = create_user();
        $other = create_user();
        $upload = DuplicateUpload::factory()->for($owner)->createOne();

        $this->deleteAs("api/duplicate-uploads/{$upload->id}", [], $other)->assertForbidden();

        $this->assertDatabaseHas('duplicate_uploads', ['id' => $upload->id]);
    }

    #[Test]
    public function discardSingleDuplicate(): void
    {
        $user = create_user();
        $upload = DuplicateUpload::factory()->for($user)->createOne();

        $this->deleteAs("api/duplicate-uploads/{$upload->id}", [], $user)->assertNoContent();

        $this->assertDatabaseMissing('duplicate_uploads', ['id' => $upload->id]);
    }

    #[Test]
    public function discardAllDuplicates(): void
    {
        $user = create_user();
        DuplicateUpload::factory()->for($user)->createMany([[], [], []]);

        $this->deleteAs('api/duplicate-uploads', [], $user)->assertNoContent();

        self::assertSame(0, DuplicateUpload::query()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function discardAllDoesNotAffectOtherUsers(): void
    {
        $user = create_user();
        $other = create_user();

        DuplicateUpload::factory()->for($user)->createMany([[], []]);
        DuplicateUpload::factory()->for($other)->createMany([[], [], []]);

        $this->deleteAs('api/duplicate-uploads', [], $user)->assertNoContent();

        self::assertSame(0, DuplicateUpload::query()->where('user_id', $user->id)->count());
        self::assertSame(3, DuplicateUpload::query()->where('user_id', $other->id)->count());
    }

    #[Test]
    public function keepSingleDuplicate(): void
    {
        Setting::set('media_path', public_path('sandbox/media'));

        $user = create_user();
        $songPath = public_path('sandbox/media/keep-test.mp3');
        File::copy(test_path('songs/full.mp3'), $songPath);

        $upload = DuplicateUpload::factory()->for($user)->createOne(['location' => $songPath]);

        $this->postAs("api/duplicate-uploads/{$upload->id}", [], $user)->assertJsonStructure(['song', 'album']);

        $this->assertDatabaseMissing('duplicate_uploads', ['id' => $upload->id]);
    }

    #[Test]
    public function keepAllDuplicates(): void
    {
        Setting::set('media_path', public_path('sandbox/media'));

        $user = create_user();

        foreach (range(1, 2) as $i) {
            $songPath = public_path("sandbox/media/keep-all-test-{$i}.mp3");
            File::copy(test_path('songs/full.mp3'), $songPath);
            DuplicateUpload::factory()->for($user)->createOne(['location' => $songPath]);
        }

        $this->postAs('api/duplicate-uploads', [], $user)->assertSuccessful()->assertJsonStructure([['song', 'album']]);

        self::assertSame(0, DuplicateUpload::query()->where('user_id', $user->id)->count());
    }
}
