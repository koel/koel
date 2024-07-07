<?php

namespace Tests\Integration\KoelPlus\Services;

use App\Events\NewPlaylistCollaboratorJoined;
use App\Exceptions\CannotRemoveOwnerFromPlaylistException;
use App\Exceptions\NotAPlaylistCollaboratorException;
use App\Exceptions\OperationNotApplicableForSmartPlaylistException;
use App\Exceptions\PlaylistCollaborationTokenExpiredException;
use App\Models\Playlist;
use App\Models\PlaylistCollaborationToken;
use App\Services\PlaylistCollaborationService;
use Illuminate\Support\Facades\Event;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlaylistCollaborationServiceTest extends PlusTestCase
{
    private PlaylistCollaborationService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlaylistCollaborationService::class);
    }

    public function testCreateToken(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $token = $this->service->createToken($playlist);

        self::assertNotNull($token->token);
        self::assertFalse($token->expired);
        self::assertSame($playlist->id, $token->playlist_id);
    }

    public function testCreateTokenFailsIfPlaylistIsSmart(): void
    {
        $this->expectException(OperationNotApplicableForSmartPlaylistException::class);

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->smart()->create();

        $this->service->createToken($playlist);
    }

    public function testAcceptUsingToken(): void
    {
        Event::fake(NewPlaylistCollaboratorJoined::class);

        /** @var PlaylistCollaborationToken $token */
        $token = PlaylistCollaborationToken::factory()->create();
        $user = create_user();
        self::assertFalse($token->playlist->collaborators->contains($user));

        $this->service->acceptUsingToken($token->token, $user);

        self::assertTrue($token->refresh()->playlist->collaborators->contains($user));
        Event::assertDispatched(NewPlaylistCollaboratorJoined::class);
    }

    public function testFailsToAcceptExpiredToken(): void
    {
        $this->expectException(PlaylistCollaborationTokenExpiredException::class);
        Event::fake(NewPlaylistCollaboratorJoined::class);

        /** @var PlaylistCollaborationToken $token */
        $token = PlaylistCollaborationToken::factory()->create();
        $user = create_user();

        $this->travel(8)->days();

        $this->service->acceptUsingToken($token->token, $user);

        self::assertFalse($token->refresh()->playlist->collaborators->contains($user));
        Event::assertNotDispatched(NewPlaylistCollaboratorJoined::class);
    }

    public function testGetCollaborators(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $user = create_user();
        $playlist->addCollaborator($user);

        $collaborators = $this->service->getCollaborators($playlist->refresh());

        self::assertEqualsCanonicalizing([$playlist->user_id, $user->id], $collaborators->pluck('id')->toArray());
    }

    public function testRemoveCollaborator(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $user = create_user();
        $playlist->addCollaborator($user);

        self::assertTrue($playlist->refresh()->hasCollaborator($user));

        $this->service->removeCollaborator($playlist, $user);

        self::assertFalse($playlist->refresh()->hasCollaborator($user));
    }

    public function testCannotRemoveNonExistingCollaborator(): void
    {
        $this->expectException(NotAPlaylistCollaboratorException::class);

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $user = create_user();

        $this->service->removeCollaborator($playlist, $user);
    }

    public function testCannotRemoveOwner(): void
    {
        $this->expectException(CannotRemoveOwnerFromPlaylistException::class);

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $this->service->removeCollaborator($playlist, $playlist->user);
    }
}
