<?php

namespace App\Builders;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

class PodcastBuilder extends Builder
{
    public function subscribedBy(User $user): self
    {
        return $this->join('podcast_user', static function (JoinClause $join) use ($user): void {
            $join->on('podcasts.id', 'podcast_user.podcast_id')
                ->where('user_id', $user->id);
        });
    }
}
