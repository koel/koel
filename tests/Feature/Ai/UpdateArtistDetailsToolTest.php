<?php

namespace Tests\Feature\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\UpdateArtistDetails;
use App\Models\Artist;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

class UpdateArtistDetailsToolTest extends TestCase
{
    #[Test]
    public function renamesArtist(): void
    {
        $user = create_admin();
        $artist = Artist::factory()->for($user)->create(['name' => 'Beetles']);

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(UpdateArtistDetails::class);
        $response = $tool->handle(new Request(['current_name' => 'Beetles', 'new_name' => 'Beatles']));

        self::assertStringContainsString('Renamed', (string) $response);
        self::assertStringContainsString('Beetles', (string) $response);
        self::assertStringContainsString('Beatles', (string) $response);
        self::assertSame('Beatles', $artist->fresh()->name);
        self::assertSame('update_artist', $result->action);
    }

    #[Test]
    public function returnsErrorWhenArtistNotFound(): void
    {
        $user = create_admin();

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(UpdateArtistDetails::class);
        $response = $tool->handle(new Request(['current_name' => 'Nonexistent', 'new_name' => 'Whatever']));

        self::assertStringContainsString('No artist matching', (string) $response);
        self::assertNull($result->action);
    }

    #[Test]
    public function deniesUnauthorizedUser(): void
    {
        $user = create_user();
        $artist = Artist::factory()->for($user)->create(['name' => 'My Artist']);

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(UpdateArtistDetails::class);
        $response = $tool->handle(new Request(['current_name' => 'My Artist', 'new_name' => 'Hacked']));

        self::assertStringContainsString("don't have permission", (string) $response);
        self::assertSame('My Artist', $artist->fresh()->name);
        self::assertNull($result->action);
    }
}
