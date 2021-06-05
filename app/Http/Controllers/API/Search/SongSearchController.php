<?php

namespace App\Http\Controllers\API\Search;

use App\Http\Controllers\API\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SongSearchController extends Controller
{
    private SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function index(Request $request)
    {
        if (!$request->get('q')) {
            throw new InvalidArgumentException('A search query is required.');
        }

        return [
            'songs' => $this->searchService->searchSongs($request->get('q')),
        ];
    }
}
