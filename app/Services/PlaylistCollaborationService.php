<?php

namespace App\Services;

use App\Facades\License;
use App\Models\Playlist;
use App\Models\PlaylistCollaborationToken;
use App\Models\User;
use App\Values\PlaylistCollaborator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webmozart\Assert\Assert;

class PlaylistCollaborationService
{
    public function createToken(Playlist $playlist): PlaylistCollaborationToken
    {
        self::assertKoelPlus();
        Assert::false($playlist->is_smart, 'Smart playlists are not collaborative.');

        return $playlist->collaborationTokens()->create();
    }

    public function acceptUsingToken(string $token, User $user): Playlist
    {
        self::assertKoelPlus();

        /** @var PlaylistCollaborationToken $collaborationToken */
        $collaborationToken = PlaylistCollaborationToken::query()->where('token', $token)->firstOrFail();

        Assert::false($collaborationToken->expired, 'The token has expired.');

        if ($collaborationToken->playlist->ownedBy($user)) {
            return $collaborationToken->playlist;
        }

        $collaborationToken->playlist->addCollaborator($user);

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

        DB::transaction(static function () use ($playlist, $user): void {
            $playlist->collaborators()->detach($user);
            $playlist->songs()->wherePivot('user_id', $user->id)->detach();
        });
    }

    private static function assertKoelPlus(): void
    {
        Assert::true(License::isPlus(), 'Playlist collaboration is only available with Koel Plus.');
    }
}
