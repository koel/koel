<?php

namespace App\Enums;

enum PlayableType: string
{
    case SONG = 'song';
    case PODCAST_EPISODE = 'episode';
}
