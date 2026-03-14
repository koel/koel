<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\UpdateArtistDetails;
use App\Models\Artist;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;

class UpdateArtistDetailsToolTest extends PlusTestCase
{
    private AiAssistantResult $result;
    private User $user;
    private UpdateArtistDetails $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_admin();
        $this->result = new AiAssistantResult();

        app()->instance(AiAssistantResult::class, $this->result);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(UpdateArtistDetails::class);
    }

    #[Test]
    public function renamesArtist(): void
    {
        $artist = Artist::factory()->for($this->user)->createOne(['name' => 'Beetles']);

        $response = $this->tool->handle(new Request(['current_name' => 'Beetles', 'new_name' => 'Beatles']));

        self::assertStringContainsString('Renamed', (string) $response);
        self::assertStringContainsString('Beetles', (string) $response);
        self::assertStringContainsString('Beatles', (string) $response);
        self::assertSame('Beatles', $artist->fresh()->name);
        self::assertSame('update_artist', $this->result->action);
    }

    #[Test]
    public function returnsErrorWhenArtistNotFound(): void
    {
        $response = $this->tool->handle(new Request(['current_name' => 'Nonexistent', 'new_name' => 'Whatever']));

        self::assertStringContainsString('No artist matching', (string) $response);
        self::assertNull($this->result->action);
    }

    #[Test]
    public function nonOwnerCannotFindArtist(): void
    {
        $owner = create_user();
        $otherUser = create_user();
        $artist = Artist::factory()->for($owner)->createOne(['name' => 'My Artist']);

        app()->instance(AiRequestContext::class, new AiRequestContext($otherUser));
        $this->tool = app()->make(UpdateArtistDetails::class);

        $response = $this->tool->handle(new Request(['current_name' => 'My Artist', 'new_name' => 'Hacked']));

        self::assertStringContainsString('No artist matching', (string) $response);
        self::assertSame('My Artist', $artist->fresh()->name);
        self::assertNull($this->result->action);
    }
}
