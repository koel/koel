<?php

namespace App\Repositories;

use App\Models\Song;
use App\Repositories\Traits\Searchable;
use App\Services\HelperService;

class SongRepository extends AbstractRepository
{
    use Searchable;

    private $helperService;

    public function __construct(HelperService $helperService)
    {
        parent::__construct();

        $this->helperService = $helperService;
    }

    public function getOneByPath(string $path): ?Song
    {
        return $this->getOneById($this->helperService->getFileHash($path));
    }
}
