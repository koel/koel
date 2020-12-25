<?php

namespace App\Http\Controllers\API\Search;

use App\Http\Controllers\API\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;
use InvalidArgumentException;

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
            throw new InvalidArgumentException('A search query is required.');
        }

        $count = (int) $request->get('count', SearchService::DEFAULT_EXCERPT_RESULT_COUNT);

        if ($count < 0) {
            throw new InvalidArgumentException('Invalid count parameter.');
        }

        return [
            'results' => $this->searchService->excerptSearch($request->get('q'), $count),
        ];
    }
}
