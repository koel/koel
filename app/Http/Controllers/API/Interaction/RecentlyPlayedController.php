<?php

namespace App\Http\Controllers\API\Interaction;

use App\Repositories\InteractionRepository;
use App\Services\InteractionService;
use Illuminate\Contracts\Auth\Authenticatable;

class RecentlyPlayedController extends Controller
{
    private $interactionRepository;

    public function __construct(
        InteractionService $interactionService,
        InteractionRepository $interactionRepository,
        ?Authenticatable $currentUser
    ) {
        parent::__construct($interactionService, $currentUser);

        $this->interactionRepository = $interactionRepository;
    }

    public function index(?int $count = null)
    {
        return response()->json($this->interactionRepository->getRecentlyPlayed($this->currentUser, $count));
    }
}
