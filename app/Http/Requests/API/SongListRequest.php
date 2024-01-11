<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $order
 * @property-read string $sort
 * @property-read boolean|string|integer $ownSongsOnly
 */
class SongListRequest extends Request
{
}
