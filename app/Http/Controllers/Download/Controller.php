<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\API\Controller as BaseController;
use App\Services\DownloadService;

abstract class Controller extends BaseController
{
    protected DownloadService $downloadService;

    public function __construct(DownloadService $downloadService)
    {
        $this->downloadService = $downloadService;
    }
}
