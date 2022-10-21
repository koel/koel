<?php

namespace App\Http\Requests\V6\API;

use App\Http\Requests\API\Request;

/**
 * @property-read string $genre
 * @property-read int $limit
 */
class FetchRandomSongsInGenreRequest extends Request
{
}
