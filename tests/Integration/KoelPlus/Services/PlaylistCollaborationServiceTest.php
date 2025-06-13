<?php

namespace Tests\Integration\KoelPlus\Services;

use App\Events\NewPlaylistCollaboratorJoined;
use App\Exceptions\CannotRemoveOwnerFromPlaylistException;
use App\Exceptions\NotAPlaylistCollaboratorException;
use App\Exceptions\OperationNotApplicableForSmartPlaylistException;
use App\Exceptions\PlaylistCollaborationTokenExpiredException;
use App\Models\PlaylistCollaborationToken;
use App\Services\PlaylistCollaborationService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_playlist;
use function Tests\create_user;

class PlaylistCollaborationServiceTest extends PlusTestCase
{
    private PlaylistCollaborationService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlaylistCollaborationService::class);
    }

    #[Test]
    public function createToken(): void
    {
        $playlist = create_playlist();

        $token = $this->service->createToken($playlist);

        self::assertNotNull($token->token);
        self::assertFalse($token->expired);
        self::assertSame($playlist->id, $token->playlist_id);
    }

    #[Test]
    public function createTokenFailsIfPlaylistIsSmart(): void
    {
        $this->expectException(OperationNotApplicableForSmartPlaylistException::class);

        $this->service->createToken(create_playlist(smart: true));
    }

    #[Test]
    public function acceptUsingToken(): void
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

    #[Test]
    public function failsToAcceptExpiredToken(): void
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

    #[Test]
    public function getCollaboratorsExcludingOwner(): void
    {
        $playlist = create_playlist();
        $user = create_user();
        $playlist->addCollaborator($user);

        $collaborators = $this->service->getCollaborators(playlist: $playlist->refresh());

        self::assertEqualsCanonicalizing([$user->public_id], Arr::pluck($collaborators->toArray(), 'id'));
    }

    #[Test]
    public function getCollaboratorsIncludingOwner(): void
    {
        $playlist = create_playlist();
        $user = create_user();
        $playlist->addCollaborator($user);

        $collaborators = $this->service->getCollaborators(playlist: $playlist->refresh(), includingOwner: true);

        self::assertEqualsCanonicalizing(
            [$playlist->owner->public_id, $user->public_id],
            Arr::pluck($collaborators->toArray(), 'id')
        );
    }

    #[Test]
    public function removeCollaborator(): void
    {
        $playlist = create_playlist();
        $user = create_user();
        $playlist->addCollaborator($user);

        self::assertTrue($playlist->refresh()->hasCollaborator($user));

        $this->service->removeCollaborator($playlist, $user);

        self::assertFalse($playlist->refresh()->hasCollaborator($user));
    }

    #[Test]
    public function cannotRemoveNonExistingCollaborator(): void
    {
        $this->expectException(NotAPlaylistCollaboratorException::class);

        $playlist = create_playlist();
        $user = create_user();

        $this->service->removeCollaborator($playlist, $user);
    }

    #[Test]
    public function cannotRemoveOwner(): void
    {
        $this->expectException(CannotRemoveOwnerFromPlaylistException::class);

        $playlist = create_playlist();

        $this->service->removeCollaborator($playlist, $playlist->owner);
    }
}
