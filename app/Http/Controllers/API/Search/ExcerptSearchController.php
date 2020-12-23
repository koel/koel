<?php

namespace App\Http\Controllers\API\Search;

use App\Http\Controllers\API\Controller;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class ExcerptSearchController extends Controller
{
    private $searchService;

    /** @var User */
    private $currentUser;

    public function __construct(SearchService $searchService, ?Authenticatable $currentUser)
    {
        $this->searchService = $searchService;
        $this->currentUser = $currentUser;
    }

    public function index(Request $request)
    {
        if (!$request->get('q')) {
            return ['results' => []];
        }

        return [
            'results' => $this->searchService->excerptSearch($request->get('q'), $this->currentUser),
        ];
    }
}
