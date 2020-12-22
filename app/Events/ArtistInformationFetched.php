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

    public function getArtist(): Artist
    {
        return $this->artist;
    }

    /**
     * @return array<mixed>
     */
    public function getInformation(): array
    {
        return $this->information;
    }
}
