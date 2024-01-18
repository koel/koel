<?php

namespace App\Services;

use App\Facades\License;
use App\Models\Playlist;
use App\Models\PlaylistCollaborationToken;
use App\Models\User;
use Webmozart\Assert\Assert;

class PlaylistCollaborationService
{
    public function createToken(Playlist $playlist): PlaylistCollaborationToken
    {
        Assert::true(License::isPlus(), 'Playlist collaboration is only available with Koel Plus.');
        Assert::false($playlist->is_smart, 'Smart playlists are not collaborative.');

        return $playlist->collaborationTokens()->create();
    }

    public function acceptUsingToken(string $token, User $user): Playlist
    {
        Assert::true(License::isPlus(), 'Playlist collaboration is only available with Koel Plus.');

        /** @var PlaylistCollaborationToken $collaborationToken */
        $collaborationToken = PlaylistCollaborationToken::query()->where('token', $token)->firstOrFail();

        Assert::false($collaborationToken->expired, 'The token has expired.');

        if ($collaborationToken->playlist->ownedBy($user)) {
            return $collaborationToken->playlist;
        }

        $collaborationToken->playlist->addCollaborator($user);

        return $collaborationToken->playlist;
    }
}
