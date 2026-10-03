<?php

namespace App\Observers;

use App\Helpers\Uuid;
use App\Models\User;
use App\Services\Image\ModelImageObserver;
use App\Services\Playlist\PlaylistService;
use App\Services\Podcast\PodcastService;
use App\Services\RadioService;
use App\Services\SongService;
use App\Services\Subsonic\AuthenticationService as SubsonicAuthenticationService;

class UserObserver
{
    private ModelImageObserver $avatarObserver;

    public function __construct(
        private readonly SubsonicAuthenticationService $subsonicAuth,
        private readonly SongService $songService,
        private readonly PlaylistService $playlistService,
        private readonly RadioService $radioService,
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
        $this->songService->deleteSongsByUser($user);
        $this->playlistService->deletePlaylistsByUser($user);
        $this->radioService->deleteRadioStationsByUser($user);
        $this->podcastService->unsubscribeUserFromAllPodcasts($user);
    }

    public function deleted(User $user): void
    {
        $this->avatarObserver->onModelDeleted($user);
    }
}
