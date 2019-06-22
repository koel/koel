<?php

namespace App\Http\Controllers\API\Interaction;

use App\Http\Controllers\Controller as BaseController;
use App\Services\InteractionService;

class Controller extends BaseController
{
    protected $interactionService;

    public function __construct(InteractionService $interactionService)
    {
        $this->interactionService = $interactionService;
    }
}
