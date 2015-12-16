<?php

namespace App\Events;

use App\Events\Event;

class MediaPathChanged extends Event
{
    /**
     * Path to media files.
     *
     * @var string
     */
    public $mediaPath;

    /**
     * Create a new event instance.
     *
     * @param string $mediaPath
     *
     * @return void
     */
    public function __construct($mediaPath)
    {
        $this->mediaPath = $mediaPath;
    }
}
