<?php

namespace App\Http\Controllers\API\Download;

use App\Http\Controllers\API\Controller as BaseController;
use App\Services\DownloadService;

abstract class Controller extends BaseController
{
    protected $downloadService;

    public function __construct(DownloadService $downloadService)
    {
        $this->downloadService = $downloadService;
    }
}
