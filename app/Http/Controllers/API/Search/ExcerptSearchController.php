<?php

namespace App\Http\Controllers\API\Search;

use App\Http\Controllers\API\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;

class ExcerptSearchController extends Controller
{
    private $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function index(Request $request)
    {
        if (!$request->get('q')) {
            return ['results' => []];
        }

        return [
            'results' => $this->searchService->excerptSearch($request->get('q')),
        ];
    }
}
