<?php

namespace App\Events;

use App\Models\Artist;
use Illuminate\Queue\SerializesModels;

class ArtistInformationFetched
{
    use SerializesModels;

    private $artist;
    private $information;

    public function __construct(Artist $artist, array $information)
    {
        $this->artist = $artist;
        $this->information = $information;
    }

    /**
     * @return Artist
     */
    public function getArtist()
    {
        return $this->artist;
    }

    /**
     * @return array
     */
    public function getInformation()
    {
        return $this->information;
    }
}
