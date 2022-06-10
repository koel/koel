<?php

namespace App\Http\Controllers\API\Interaction;

use App\Models\User;
use App\Repositories\InteractionRepository;
use App\Services\InteractionService;
use Illuminate\Contracts\Auth\Authenticatable;

class RecentlyPlayedController extends Controller
{
    /** @param User $user */
    public function __construct(
        protected InteractionService $interactionService,
        protected InteractionRepository $interactionRepository,
        protected ?Authenticatable $user
    ) {
        parent::__construct($interactionService, $user);
    }

    public function index(?int $count = null)
    {
        return response()->json($this->interactionRepository->getRecentlyPlayed($this->user, $count));
    }
}
