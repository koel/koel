<?php

namespace App\Http\Controllers\API\Interaction;

use App\Repositories\InteractionRepository;
use App\Services\InteractionService;
use Illuminate\Http\Request;

class RecentlyPlayedController extends Controller
{
    private $interactionRepository;

    public function __construct(InteractionService $interactionService, InteractionRepository $interactionRepository)
    {
        parent::__construct($interactionService);
        $this->interactionRepository = $interactionRepository;
    }

    public function index(Request $request, ?int $count = null)
    {
        return response()->json($this->interactionRepository->getRecentlyPlayed($request->user(), $count));
    }
}
