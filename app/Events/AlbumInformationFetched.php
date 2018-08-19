<?php

namespace App\Events;

use App\Models\Album;
use Illuminate\Queue\SerializesModels;

class AlbumInformationFetched extends Event
{
    use SerializesModels;

    private $album;
    private $information;

    public function __construct(Album $album, array $information)
    {
        $this->album = $album;
        $this->information = $information;
    }

    /**
     * @return Album
     */
    public function getAlbum()
    {
        return $this->album;
    }

    /**
     * @return array
     */
    public function getInformation()
    {
        return $this->information;
    }
}
