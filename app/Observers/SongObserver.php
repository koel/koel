<?php

namespace App\Observers;

use App\Models\Song;
use App\Services\Helper;

class SongObserver
{
    private Helper $helper;

    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
    }

    public function creating(Song $song): void
    {
        $this->setFileHashAsId($song);
    }

    private function setFileHashAsId(Song $song): void
    {
        $song->id = $this->helper->getFileHash($song->path);
    }
}
