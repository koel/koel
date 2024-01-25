<?php

namespace App\Services;

use App\Events\NewPlaylistCollaboratorJoined;
use App\Exceptions\CannotRemoveOwnerFromPlaylistException;
use App\Exceptions\KoelPlusRequiredException;
use App\Exceptions\NotAPlaylistCollaboratorException;
use App\Exceptions\PlaylistCollaborationTokenExpiredException;
use App\Exceptions\SmartPlaylistsAreNotCollaborativeException;
use App\Facades\License;
use App\Models\Playlist;
use App\Models\PlaylistCollaborationToken;
use App\Models\User;
use App\Values\PlaylistCollaborator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlaylistCollaborationService
{
    public function createToken(Playlist $playlist): PlaylistCollaborationToken
    {
        self::assertKoelPlus();

        throw_if($playlist->is_smart, SmartPlaylistsAreNotCollaborativeException::class);

        return $playlist->collaborationTokens()->create();
    }

    public function acceptUsingToken(string $token, User $user): Playlist
    {
        self::assertKoelPlus();

        /** @var PlaylistCollaborationToken $collaborationToken */
        $collaborationToken = PlaylistCollaborationToken::query()->where('token', $token)->firstOrFail();

        throw_if($collaborationToken->expired, PlaylistCollaborationTokenExpiredException::class);

        if ($collaborationToken->playlist->ownedBy($user)) {
            return $collaborationToken->playlist;
        }

        $collaborationToken->playlist->addCollaborator($user);

        // Now that we have at least one external collaborator, the songs in the playlist should be made public.
        // Here we dispatch an event for that to happen.
        event(new NewPlaylistCollaboratorJoined($user, $collaborationToken));

        return $collaborationToken->playlist;
    }

    /** @return Collection|array<array-key, PlaylistCollaborator> */
    public function getCollaborators(Playlist $playlist): Collection
    {
        self::assertKoelPlus();

        return $playlist->collaborators->unless(
            $playlist->collaborators->contains($playlist->user), // The owner is always a collaborator
            static fn (Collection $collaborators) => $collaborators->push($playlist->user)
        )
            ->map(static fn (User $user) => PlaylistCollaborator::fromUser($user));
    }

    public function removeCollaborator(Playlist $playlist, User $user): void
    {
        self::assertKoelPlus();

        throw_if($user->is($playlist->user), CannotRemoveOwnerFromPlaylistException::class);
        throw_if(!$playlist->hasCollaborator($user), NotAPlaylistCollaboratorException::class);

        DB::transaction(static function () use ($playlist, $user): void {
            $playlist->collaborators()->detach($user);
            $playlist->songs()->wherePivot('user_id', $user->id)->detach();
        });
    }

    private static function assertKoelPlus(): void
    {
        throw_unless(License::isPlus(), KoelPlusRequiredException::class);
    }
}
