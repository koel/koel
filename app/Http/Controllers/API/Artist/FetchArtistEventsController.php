<?php

namespace App\Http\Controllers\API\Artist;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\LiveEventResource;
use App\Models\Artist;
use App\Services\TicketmasterService;
use Illuminate\Http\Request;

#[RequiresPlus]
class FetchArtistEventsController extends Controller
{
    public function __invoke(
        Artist $artist,
        TicketmasterService $ticketmasterService,
        Request $request,
    ) {
        return LiveEventResource::collection(
            $ticketmasterService->searchEventForArtist($artist->name, $request->getClientIp())
        );
    }
}
