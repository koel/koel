<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $order
 * @property-read string $sort
 * @property-read boolean|string|integer $own_songs_only
 */
class SongListRequest extends Request
{
}
