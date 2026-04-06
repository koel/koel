<?php

namespace Tests\Feature;

use App\Models\DuplicateUpload;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class DuplicateUploadActionTest extends TestCase
{
    #[Test]
    public function keepSingleDuplicate(): void
    {
        $this->markTestSkipped('Requires local storage setup for scanning');
    }

    #[Test]
    public function cannotKeepOtherUsersDuplicate(): void
    {
        $owner = create_user();
        $other = create_user();
        $upload = DuplicateUpload::factory()->for($owner)->createOne();

        $this->postAs("api/duplicate-uploads/{$upload->id}/keep", [], $other)->assertForbidden();
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
    public function cannotDiscardOtherUsersDuplicate(): void
    {
        $owner = create_user();
        $other = create_user();
        $upload = DuplicateUpload::factory()->for($owner)->createOne();

        $this->deleteAs("api/duplicate-uploads/{$upload->id}", [], $other)->assertForbidden();

        $this->assertDatabaseHas('duplicate_uploads', ['id' => $upload->id]);
    }

    #[Test]
    public function discardAllDuplicates(): void
    {
        $user = create_user();
        DuplicateUpload::factory()
            ->count(3)
            ->for($user)
            ->create();

        $this->deleteAs('api/duplicate-uploads', [], $user)->assertNoContent();

        self::assertSame(0, DuplicateUpload::query()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function discardAllDoesNotAffectOtherUsers(): void
    {
        $user = create_user();
        $other = create_user();

        DuplicateUpload::factory()
            ->count(2)
            ->for($user)
            ->create();
        DuplicateUpload::factory()
            ->count(3)
            ->for($other)
            ->create();

        $this->deleteAs('api/duplicate-uploads', [], $user)->assertNoContent();

        self::assertSame(0, DuplicateUpload::query()->where('user_id', $user->id)->count());
        self::assertSame(3, DuplicateUpload::query()->where('user_id', $other->id)->count());
    }

    #[Test]
    public function keepAllDuplicates(): void
    {
        $this->markTestSkipped('Requires local storage setup for scanning');
    }
}
