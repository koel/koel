<?php

namespace App\Http\Controllers\API\Search;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SongSearchController extends Controller
{
    public function __construct(private SearchService $searchService)
    {
    }

    public function index(Request $request)
    {
        throw_unless((bool) $request->get('q'), new InvalidArgumentException('A search query is required.'));

        return [
            'songs' => $this->searchService->searchSongs($request->get('q')),
        ];
    }
}
