<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\UpdateAlbumDetails;
use App\Models\Album;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;

class UpdateAlbumDetailsToolTest extends PlusTestCase
{
    private AiAssistantResult $result;
    private User $user;
    private UpdateAlbumDetails $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_admin();
        $this->result = new AiAssistantResult();

        app()->instance(AiAssistantResult::class, $this->result);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(UpdateAlbumDetails::class);
    }

    #[Test]
    public function renamesAlbum(): void
    {
        $album = Album::factory()->for($this->user)->createOne(['name' => 'Old Album']);

        $response = $this->tool->handle(new Request(['query' => 'Old Album', 'name' => 'New Album']));

        self::assertStringContainsString('name to "New Album"', (string) $response);
        self::assertSame('New Album', $album->fresh()->name);
        self::assertSame('update_album', $this->result->action);
    }

    #[Test]
    public function updatesYear(): void
    {
        $album = Album::factory()->for($this->user)->createOne(['name' => 'My Album', 'year' => null]);

        $response = $this->tool->handle(new Request(['query' => 'My Album', 'year' => 1999]));

        self::assertStringContainsString('year to 1999', (string) $response);
        self::assertSame(1999, $album->fresh()->year);
        self::assertSame('update_album', $this->result->action);
    }

    #[Test]
    public function updatesNameAndYear(): void
    {
        $album = Album::factory()->for($this->user)->createOne(['name' => 'Old Album', 'year' => 2000]);

        $response = $this->tool->handle(new Request(['query' => 'Old Album', 'name' => 'New Album', 'year' => 2024]));

        self::assertStringContainsString('name to "New Album"', (string) $response);
        self::assertStringContainsString('year to 2024', (string) $response);

        $fresh = $album->fresh();
        self::assertSame('New Album', $fresh->name);
        self::assertSame(2024, $fresh->year);
    }

    #[Test]
    public function returnsErrorWhenAlbumNotFound(): void
    {
        $response = $this->tool->handle(new Request(['query' => 'Nonexistent', 'name' => 'Whatever']));

        self::assertStringContainsString('No album matching', (string) $response);
        self::assertNull($this->result->action);
    }

    #[Test]
    public function returnsNoChangesWhenNothingProvided(): void
    {
        Album::factory()->for($this->user)->createOne(['name' => 'My Album']);

        $response = $this->tool->handle(new Request(['query' => 'My Album']));

        self::assertStringContainsString('No changes', (string) $response);
    }

    #[Test]
    public function nonOwnerCannotFindAlbum(): void
    {
        $owner = create_user();
        $otherUser = create_user();
        $album = Album::factory()->for($owner)->createOne(['name' => 'My Album']);

        app()->instance(AiRequestContext::class, new AiRequestContext($otherUser));
        $this->tool = app()->make(UpdateAlbumDetails::class);

        $response = $this->tool->handle(new Request(['query' => 'My Album', 'name' => 'Hacked']));

        self::assertStringContainsString('No album matching', (string) $response);
        self::assertSame('My Album', $album->fresh()->name);
        self::assertNull($this->result->action);
    }
}
