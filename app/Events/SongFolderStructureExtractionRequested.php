<?php

namespace App\Events;

use App\Models\Song;

class SongFolderStructureExtractionRequested extends Event
{
    public function __construct(public Song $song)
    {
    }
}
