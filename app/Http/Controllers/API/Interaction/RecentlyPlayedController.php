<?php

namespace App\Http\Controllers\API\Interaction;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\InteractionRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class RecentlyPlayedController extends Controller
{
    /** @param User $user */
    public function __construct(private InteractionRepository $interactionRepository, private ?Authenticatable $user)
    {
    }

    public function index(?int $count = null)
    {
        return response()->json($this->interactionRepository->getRecentlyPlayed($this->user, $count));
    }
}
