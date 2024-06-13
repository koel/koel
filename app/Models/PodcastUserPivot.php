<?php

namespace App\Models;

use App\Casts\Podcast\PodcastStateCast;
use App\Values\Podcast\PodcastState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property PodcastState $state
 */
class PodcastUserPivot extends Pivot
{
    protected $table = 'podcast_user';

    protected $guarded = [];
    protected $appends = ['meta'];

    protected $casts = [
        'state' => PodcastStateCast::class,
    ];
}
