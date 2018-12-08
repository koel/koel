<?php

namespace App\Http\Controllers\API\Interaction;

use App\Repositories\InteractionRepository;
use App\Services\InteractionService;
use Illuminate\Http\Request;

/**
 * @group 3. Song interactions
 */
class RecentlyPlayedController extends Controller
{
    private $interactionRepository;

    public function __construct(InteractionService $interactionService, InteractionRepository $interactionRepository)
    {
        parent::__construct($interactionService);
        $this->interactionRepository = $interactionRepository;
    }

    /**
     * Get recently played songs
     *
     * Get a list of songs recently played by the current user.
     *
     * @queryParam count The maximum number of songs to be returned. Example: 2
     * @response ["0146d01afb742b01f28ab8b556f9a75d", "c741133cb8d1982a5c60b1ce2a1e6e47"]
     */
    public function index(Request $request, ?int $count = null)
    {
        return response()->json($this->interactionRepository->getRecentlyPlayed($request->user(), $count));
    }
}
