<?php

namespace App\Http\Controllers\API\Download;

use App\Http\Controllers\API\Controller as BaseController;
use App\Services\Download;

abstract class Controller extends BaseController
{
    protected $downloadService;

    public function __construct(Download $downloadService)
    {
        $this->downloadService = $downloadService;
    }
}
