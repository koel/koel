<?php

namespace App\Observers;

use App\Helpers\Uuid;
use App\Models\Playlist;
use App\Models\Podcast;
use App\Models\RadioStation;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\Image\ModelImageObserver;
use App\Services\Podcast\PodcastService;
use App\Services\SongService;
use App\Services\Subsonic\AuthenticationService as SubsonicAuthenticationService;

class UserObserver
{
    private ModelImageObserver $avatarObserver;

    public function __construct(
        private readonly SubsonicAuthenticationService $subsonicAuth,
        private readonly SongRepository $songRepository,
        private readonly SongService $songService,
        private readonly PodcastService $podcastService,
    ) {
        $this->avatarObserver = ModelImageObserver::make('avatar');
    }

    public function creating(User $user): void
    {
        $user->public_id ??= Uuid::generate();

        if (!$user->subsonic_api_key) {
            $this->subsonicAuth->assignApiKey($user, save: false);
        }
    }

    public function updating(User $user): void
    {
        $this->avatarObserver->onModelUpdating($user);
    }

    public function deleting(User $user): void
    {
        $this->songService->deleteSongs($this->songRepository->getIdsByOwner($user));
        $user->loadMissing(['ownedPlaylists', 'radioStations', 'podcasts']);
        $user->ownedPlaylists->each(static fn (Playlist $playlist) => $playlist->delete());
        $user->radioStations->each(static fn (RadioStation $station) => $station->delete());
        $user->podcasts->each(
            fn (Podcast $podcast) => $this->podcastService->unsubscribeUserFromPodcast($user, $podcast),
        );
    }

    public function deleted(User $user): void
    {
        $this->avatarObserver->onModelDeleted($user);
    }
}
