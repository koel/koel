<?php

namespace App\Builders;

use App\Builders\Concerns\CanScopeByUser;
use Illuminate\Database\Query\JoinClause;
use LogicException;

class PodcastBuilder extends FavoriteableBuilder
{
    use CanScopeByUser;

    public function subscribed(): self
    {
        throw_if(!$this->user, new LogicException('User must be set to query subscribed podcasts.'));

        return $this->join('podcast_user', function (JoinClause $join): void {
            $join->on('podcasts.id', 'podcast_user.podcast_id')
                ->where('podcast_user.user_id', $this->user->id);
        });
    }
}
