<?php

namespace App\Http\Controllers\API\Interaction;

use App\Http\Controllers\Controller as BaseController;
use App\Models\User;
use App\Services\InteractionService;
use Illuminate\Contracts\Auth\Authenticatable;

class Controller extends BaseController
{
    protected $interactionService;

    /** @var User */
    protected $currentUser;

    public function __construct(InteractionService $interactionService, ?Authenticatable $currentUser)
    {
        $this->interactionService = $interactionService;
        $this->currentUser = $currentUser;
    }
}
