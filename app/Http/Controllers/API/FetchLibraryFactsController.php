<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\LibraryFactResource;
use App\Models\User;
use App\Services\LibraryFactService;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchLibraryFactsController extends Controller
{
    public function __construct(
        private readonly LibraryFactService $libraryFactService,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        return LibraryFactResource::collection($this->libraryFactService->getFacts($user));
    }
}
