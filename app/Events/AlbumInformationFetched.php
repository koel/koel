<?php

namespace App\Events;

use App\Models\Album;
use App\Values\AlbumInformation;
use Illuminate\Queue\SerializesModels;

class AlbumInformationFetched extends Event
{
    use SerializesModels;

    public function __construct(public Album $album, public AlbumInformation $information)
    {
    }
}
