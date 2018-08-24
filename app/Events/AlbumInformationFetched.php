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

    public function getAlbum(): Album
    {
        return $this->album;
    }

    /**
     * @return mixed[]
     */
    public function getInformation(): array
    {
        return $this->information;
    }
}
