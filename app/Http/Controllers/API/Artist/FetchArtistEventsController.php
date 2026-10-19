<?php

namespace App\Http\Controllers\API\Artist;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\LiveEventResource;
use App\Models\Artist;
use App\Services\Integrations\TicketmasterService;
use Illuminate\Http\Request;

#[RequiresPlus]
class FetchArtistEventsController extends Controller
{
    public function __invoke(Artist $artist, TicketmasterService $ticketmasterService, Request $request)
    {
        $this->authorize('access', $artist);

        $result = $ticketmasterService->searchEventForArtist($artist, $request->getClientIp());

        return response()->json([
            'events' => LiveEventResource::collection($result->events),
            'nearby' => $result->nearby,
        ]);
    }
}
