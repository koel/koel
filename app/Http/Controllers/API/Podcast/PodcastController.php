<?php

namespace App\Http\Controllers\API\Podcast;

use App\Attributes\DisabledInDemo;
use App\Exceptions\UserAlreadySubscribedToPodcastException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Podcast\PodcastStoreRequest;
use App\Http\Resources\PodcastResource;
use App\Models\Podcast;
use App\Models\User;
use App\Repositories\PodcastRepository;
use App\Services\PodcastService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PodcastController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly PodcastService $podcastService,
        private readonly PodcastRepository $podcastRepository,
        private readonly Authenticatable $user
    ) {
    }

    public function index(Request $request)
    {
        return PodcastResource::collection(
            $this->podcastRepository->getAllSubscribedByUser($request->boolean('favorites_only'), $this->user)
        );
    }

    #[DisabledInDemo]
    public function store(PodcastStoreRequest $request)
    {
        try {
            return PodcastResource::make($this->podcastService->addPodcast($request->url, $this->user));
        } catch (UserAlreadySubscribedToPodcastException) {
            abort(Response::HTTP_CONFLICT, 'You have already subscribed to this podcast.');
        }
    }

    public function show(Podcast $podcast)
    {
        $this->authorize('view', $podcast);

        return PodcastResource::make($podcast);
    }
}
