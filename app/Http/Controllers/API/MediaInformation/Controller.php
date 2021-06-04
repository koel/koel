<?php

namespace App\Http\Controllers\API\MediaInformation;

use App\Http\Controllers\API\Controller as BaseController;
use App\Services\MediaInformationService;

class Controller extends BaseController
{
    protected MediaInformationService $mediaInformationService;

    public function __construct(MediaInformationService $mediaInformationService)
    {
        $this->mediaInformationService = $mediaInformationService;
    }
}
