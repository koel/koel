<?php

namespace App\Http\Controllers\API;

use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\PlaylistRepository;
use App\Repositories\SongRepository;

class SearchController extends Controller
{
    private $songRepository;
    private $albumRepository;
    private $artistRepository;
    private $playlistRepository;

    public function __construct(
        SongRepository $songRepository,
        AlbumRepository $albumRepository,
        ArtistRepository $artistRepository,
        PlaylistRepository $playlistRepository
    ) {
        $this->songRepository = $songRepository;
        $this->albumRepository = $albumRepository;
        $this->artistRepository = $artistRepository;
        $this->playlistRepository = $playlistRepository;
    }

    public function index()
    {
    }
}
