<?php

namespace App\Services;

use App\Models\Song;

class TranscodingService
{
    /**
     * Determine if a song should be transcoded.
     *
     * @param Song $song
     *
     * @return bool
     */
    public function songShouldBeTranscoded(Song $song)
    {
        return ends_with(mime_content_type($song->path), 'flac');
    }
}
