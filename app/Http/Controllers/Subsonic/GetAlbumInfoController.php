<?php

namespace App\Http\Controllers\Subsonic;

/**
 * Subsonic v1 `getAlbumInfo`. The response shape is identical to `getAlbumInfo2`;
 * the spec difference is only the kind of id the client passes (folder vs ID3),
 * and koel has a single album id space, so both endpoints resolve identically.
 */
class GetAlbumInfoController extends GetAlbumInfo2Controller {}
