<?php

namespace App\Http\Requests\Subsonic;

/**
 * The Subsonic v1 `getAlbumList` accepts the same parameters as the v2/v3 form,
 * differing only in the response wrapper element and the child shape. Inheriting
 * keeps the rule + type list in one place.
 *
 * @property string $type
 */
class GetAlbumListRequest extends GetAlbumList2Request {}
