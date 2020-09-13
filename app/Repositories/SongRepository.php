<?php

namespace App\Repositories;

use App\Models\Song;
use App\Services\HelperService;

class SongRepository extends AbstractRepository
{
    private $helperService;

    public function __construct(HelperService $helperService)
    {
        parent::__construct();
        $this->helperService = $helperService;
    }

    public function getModelClass(): string
    {
        return Song::class;
    }

    public function getOneByPath(string $path): ?Song
    {
        return $this->getOneById($this->helperService->getFileHash($path));
    }
}
