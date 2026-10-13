<?php

namespace App\Builders;

use App\Builders\Concerns\CanScopeByUser;
use App\Models\Podcast;
use App\Models\User;
use Illuminate\Database\Query\JoinClause;
use LogicException;

/**
 * @extends FavoriteableBuilder<Podcast>
 */
class PodcastBuilder extends FavoriteableBuilder
{
    use CanScopeByUser;

    public function subscribed(): self
    {
        throw_if(!$this->user, new LogicException('User must be set to query subscribed podcasts.'));

        return $this->join('podcast_user', function (JoinClause $join): void {
            $join->on('podcasts.id', 'podcast_user.podcast_id')->where('podcast_user.user_id', $this->user->id);
        });
    }

    public function withUserContext(User $user, bool $favoritesOnly = false): self
    {
        $this->user = $user;

        return $this->withFavoriteStatus(favoritesOnly: $favoritesOnly)->withRatingSubquery();
    }
}
