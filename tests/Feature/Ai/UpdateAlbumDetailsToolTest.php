<?php

namespace Tests\Feature\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\UpdateAlbumDetails;
use App\Models\Album;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

class UpdateAlbumDetailsToolTest extends TestCase
{
    #[Test]
    public function renamesAlbum(): void
    {
        $user = create_admin();
        $album = Album::factory()->for($user)->create(['name' => 'Old Album']);

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(UpdateAlbumDetails::class);
        $response = $tool->handle(new Request(['query' => 'Old Album', 'name' => 'New Album']));

        self::assertStringContainsString('name to "New Album"', (string) $response);
        self::assertSame('New Album', $album->fresh()->name);
        self::assertSame('update_album', $result->action);
    }

    #[Test]
    public function updatesYear(): void
    {
        $user = create_admin();
        $album = Album::factory()->for($user)->create(['name' => 'My Album', 'year' => null]);

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(UpdateAlbumDetails::class);
        $response = $tool->handle(new Request(['query' => 'My Album', 'year' => 1999]));

        self::assertStringContainsString('year to 1999', (string) $response);
        self::assertSame(1999, $album->fresh()->year);
        self::assertSame('update_album', $result->action);
    }

    #[Test]
    public function updatesNameAndYear(): void
    {
        $user = create_admin();
        $album = Album::factory()->for($user)->create(['name' => 'Old Album', 'year' => 2000]);

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(UpdateAlbumDetails::class);
        $response = $tool->handle(new Request(['query' => 'Old Album', 'name' => 'New Album', 'year' => 2024]));

        self::assertStringContainsString('name to "New Album"', (string) $response);
        self::assertStringContainsString('year to 2024', (string) $response);

        $fresh = $album->fresh();
        self::assertSame('New Album', $fresh->name);
        self::assertSame(2024, $fresh->year);
    }

    #[Test]
    public function returnsErrorWhenAlbumNotFound(): void
    {
        $user = create_admin();

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(UpdateAlbumDetails::class);
        $response = $tool->handle(new Request(['query' => 'Nonexistent', 'name' => 'Whatever']));

        self::assertStringContainsString('No album matching', (string) $response);
        self::assertNull($result->action);
    }

    #[Test]
    public function returnsNoChangesWhenNothingProvided(): void
    {
        $user = create_admin();
        $album = Album::factory()->for($user)->create(['name' => 'My Album']);

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(UpdateAlbumDetails::class);
        $response = $tool->handle(new Request(['query' => 'My Album']));

        self::assertStringContainsString('No changes', (string) $response);
    }

    #[Test]
    public function deniesUnauthorizedUser(): void
    {
        $user = create_user();
        $album = Album::factory()->for($user)->create(['name' => 'My Album']);

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(UpdateAlbumDetails::class);
        $response = $tool->handle(new Request(['query' => 'My Album', 'name' => 'Hacked']));

        self::assertStringContainsString("don't have permission", (string) $response);
        self::assertSame('My Album', $album->fresh()->name);
        self::assertNull($result->action);
    }
}
