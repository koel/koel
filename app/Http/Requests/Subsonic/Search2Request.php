<?php

namespace App\Http\Requests\Subsonic;

/**
 * The Subsonic v2 `search2` endpoint accepts the same parameters as `search3`,
 * differing only in the response wrapper element and the album shape (Child vs
 * AlbumID3). Inheriting keeps the rules in one place.
 */
class Search2Request extends Search3Request {}
