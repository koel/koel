<?php

namespace App\Events;

use App\Models\Artist;
use App\Values\ArtistInformation;
use Illuminate\Queue\SerializesModels;

class ArtistInformationFetched
{
    use SerializesModels;

    public function __construct(public Artist $artist, public ArtistInformation $information)
    {
    }
}
