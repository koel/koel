<?php

namespace App\Services;

use App\Events\NewPlaylistCollaboratorJoined;
use App\Exceptions\CannotRemoveOwnerFromPlaylistException;
use App\Exceptions\NotAPlaylistCollaboratorException;
use App\Exceptions\OperationNotApplicableForSmartPlaylistException;
use App\Exceptions\PlaylistCollaborationTokenExpiredException;
use App\Models\Playlist;
use App\Models\PlaylistCollaborationToken;
use App\Models\User;
use App\Values\Playlist\PlaylistCollaborator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlaylistCollaborationService
{
    public function createToken(Playlist $playlist): PlaylistCollaborationToken
    {
        throw_if($playlist->is_smart, OperationNotApplicableForSmartPlaylistException::class);

        return $playlist->collaborationTokens()->create();
    }

    public function acceptUsingToken(string $token, User $user): Playlist
    {
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

    /** @return Collection<array-key, PlaylistCollaborator> */
    public function getCollaborators(Playlist $playlist, bool $includingOwner = false): Collection
    {
        $collaborators = $includingOwner ? $playlist->users : $playlist->collaborators;

        return $collaborators->map(static fn (User $user) => PlaylistCollaborator::fromUser($user));
    }

    public function removeCollaborator(Playlist $playlist, User $user): void
    {
        throw_if($playlist->ownedBy($user), CannotRemoveOwnerFromPlaylistException::class);
        throw_if(!$playlist->hasCollaborator($user), NotAPlaylistCollaboratorException::class);

        DB::transaction(static function () use ($playlist, $user): void {
            $playlist->collaborators()->detach($user);
            $playlist->playables()->wherePivot('user_id', $user->id)->detach();
        });
    }
}
