<?php

namespace App\Services;

use App\Models\Song;

class TranscodingService
{
    public function songShouldBeTranscoded(Song $song): bool
    {
        return ends_with(mime_content_type($song->path), 'flac');
    }
}
