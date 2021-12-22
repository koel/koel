<?php

namespace App\Repositories;

use App\Models\Song;
use App\Repositories\Traits\Searchable;
use App\Services\Helper;
use Illuminate\Support\Collection;

class SongRepository extends AbstractRepository
{
    use Searchable;

    private Helper $helper;

    public function __construct(Helper $helper)
    {
        parent::__construct();

        $this->helper = $helper;
    }

    public function getOneByPath(string $path): ?Song
    {
        return $this->getOneById($this->helper->getFileHash($path));
    }

    /** @return Collection|array<Song> */
    public function getAllHostedOnS3(): Collection
    {
        return Song::hostedOnS3()->get();
    }
}
