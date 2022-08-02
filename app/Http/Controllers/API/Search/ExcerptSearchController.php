<?php

namespace App\Http\Controllers\API\Search;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ExcerptSearchController extends Controller
{
    public function __construct(private SearchService $searchService)
    {
    }

    public function index(Request $request)
    {
        throw_unless((bool) $request->get('q'), new InvalidArgumentException('A search query is required.'));

        $count = (int) $request->get('count', SearchService::DEFAULT_EXCERPT_RESULT_COUNT);
        throw_if($count < 0, new InvalidArgumentException('Invalid count parameter.'));

        return [
            'results' => $this->searchService->excerptSearch($request->get('q'), $count),
        ];
    }
}
