<?php

namespace App\Http\Controllers\API\Podcast;

use App\Exceptions\UserAlreadySubscribedToPodcast;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Podcast\PodcastStoreRequest;
use App\Http\Resources\PodcastResource;
use App\Http\Resources\PodcastResourceCollection;
use App\Models\Podcast\Podcast;
use App\Models\User;
use App\Repositories\PodcastRepository;
use App\Services\PodcastService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class PodcastController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly PodcastService $podcastService,
        private readonly PodcastRepository $podcastRepository,
        private readonly ?Authenticatable $user
    ) {
    }

    public function index()
    {
        return PodcastResourceCollection::make($this->podcastRepository->getAllByUser($this->user));
    }

    public function store(PodcastStoreRequest $request)
    {
        self::disableInDemo();

        try {
            return PodcastResource::make($this->podcastService->addPodcast($request->url, $this->user));
        } catch (UserAlreadySubscribedToPodcast) {
            abort(Response::HTTP_CONFLICT, 'You have already subscribed to this podcast.');
        }
    }

    public function show(Podcast $podcast)
    {
        $this->authorize('view', $podcast);

        return PodcastResource::make($podcast);
    }
}
