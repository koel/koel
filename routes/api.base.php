<?php

use App\Facades\YouTube;
use App\Http\Controllers\API\ActivateLicenseController;
use App\Http\Controllers\API\AlbumController;
use App\Http\Controllers\API\AlbumSongController;
use App\Http\Controllers\API\ArtistAlbumController;
use App\Http\Controllers\API\ArtistController;
use App\Http\Controllers\API\ArtistSongController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DisconnectFromLastfmController;
use App\Http\Controllers\API\ExcerptSearchController;
use App\Http\Controllers\API\FetchAlbumInformationController;
use App\Http\Controllers\API\FetchAlbumThumbnailController;
use App\Http\Controllers\API\FetchArtistInformationController;
use App\Http\Controllers\API\FetchDemoCreditsController;
use App\Http\Controllers\API\FetchFavoriteSongsController;
use App\Http\Controllers\API\FetchInitialDataController;
use App\Http\Controllers\API\FetchOverviewController;
use App\Http\Controllers\API\FetchRandomSongsInGenreController;
use App\Http\Controllers\API\FetchRecentlyPlayedSongController;
use App\Http\Controllers\API\FetchSongsForQueueController;
use App\Http\Controllers\API\ForgotPasswordController;
use App\Http\Controllers\API\GenreController;
use App\Http\Controllers\API\GenreSongController;
use App\Http\Controllers\API\GetOneTimeTokenController;
use App\Http\Controllers\API\LambdaSongController as S3SongController;
use App\Http\Controllers\API\LikeMultipleSongsController;
use App\Http\Controllers\API\MovePlaylistSongsController;
use App\Http\Controllers\API\PlaylistCollaboration\AcceptPlaylistCollaborationInviteController;
use App\Http\Controllers\API\PlaylistCollaboration\CreatePlaylistCollaborationTokenController;
use App\Http\Controllers\API\PlaylistCollaboration\PlaylistCollaboratorController;
use App\Http\Controllers\API\PlaylistController;
use App\Http\Controllers\API\PlaylistCoverController;
use App\Http\Controllers\API\PlaylistFolderController;
use App\Http\Controllers\API\PlaylistFolderPlaylistController;
use App\Http\Controllers\API\PlaylistSongController;
use App\Http\Controllers\API\Podcast\FetchEpisodeController;
use App\Http\Controllers\API\Podcast\PodcastController;
use App\Http\Controllers\API\Podcast\PodcastEpisodeController;
use App\Http\Controllers\API\Podcast\UnsubscribeFromPodcastController;
use App\Http\Controllers\API\PrivatizeSongsController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\PublicizeSongsController;
use App\Http\Controllers\API\QueueStateController;
use App\Http\Controllers\API\RegisterPlayController;
use App\Http\Controllers\API\ResetPasswordController;
use App\Http\Controllers\API\ScrobbleController;
use App\Http\Controllers\API\SearchYouTubeController;
use App\Http\Controllers\API\SetLastfmSessionKeyController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\SongController;
use App\Http\Controllers\API\SongSearchController;
use App\Http\Controllers\API\ToggleLikeSongController;
use App\Http\Controllers\API\UnlikeMultipleSongsController;
use App\Http\Controllers\API\UpdatePlaybackStatusController;
use App\Http\Controllers\API\UpdateUserPreferenceController;
use App\Http\Controllers\API\UploadAlbumCoverController;
use App\Http\Controllers\API\UploadArtistImageController;
use App\Http\Controllers\API\UploadController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UserInvitationController;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Pusher\Pusher;

Route::prefix('api')->middleware('api')->group(static function (): void {
    Route::get('ping', static fn () => null);

    Route::post('me', [AuthController::class, 'login'])->name('auth.login');
    Route::post('me/otp', [AuthController::class, 'loginUsingOneTimeToken']);

    Route::delete('me', [AuthController::class, 'logout']);

    Route::post('forgot-password', ForgotPasswordController::class);
    Route::post('reset-password', ResetPasswordController::class);

    Route::get('invitations', [UserInvitationController::class, 'get']);
    Route::post('invitations/accept', [UserInvitationController::class, 'accept']);

    Route::middleware('auth')->group(static function (): void {
        Route::get('one-time-token', GetOneTimeTokenController::class);
        Route::post('broadcasting/auth', static function (Request $request) {
            $pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                [
                    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                    'encrypted' => true,
                ]
            );

            return $pusher->socket_auth($request->channel_name, $request->socket_id);
        })->name('broadcasting.auth');

        Route::get('overview', FetchOverviewController::class);
        Route::get('data', FetchInitialDataController::class);

        Route::get('queue/fetch', FetchSongsForQueueController::class);
        Route::put('queue/playback-status', UpdatePlaybackStatusController::class);
        Route::get('queue/state', [QueueStateController::class, 'show']);
        Route::put('queue/state', [QueueStateController::class, 'update']);

        Route::put('settings', [SettingController::class, 'update']);

        Route::apiResource('albums', AlbumController::class);
        Route::apiResource('albums.songs', AlbumSongController::class);

        Route::apiResource('artists', ArtistController::class);
        Route::apiResource('artists.albums', ArtistAlbumController::class);
        Route::apiResource('artists.songs', ArtistSongController::class);

        Route::post('songs/{song}/scrobble', ScrobbleController::class)->where(['song' => Song::ID_REGEX]);

        Route::apiResource('songs', SongController::class)
            ->except('update', 'destroy')
            ->where(['song' => Song::ID_REGEX]);

        Route::put('songs', [SongController::class, 'update']);
        Route::delete('songs', [SongController::class, 'destroy']);

        Route::post('upload', UploadController::class);

        // Interaction routes
        Route::post('interaction/play', RegisterPlayController::class);
        Route::post('interaction/like', ToggleLikeSongController::class);
        Route::post('interaction/batch/like', LikeMultipleSongsController::class);
        Route::post('interaction/batch/unlike', UnlikeMultipleSongsController::class);

        Route::get('songs/recently-played', FetchRecentlyPlayedSongController::class);
        Route::get('songs/favorite', FetchFavoriteSongsController::class);

        Route::apiResource('playlist-folders', PlaylistFolderController::class);
        Route::apiResource('playlist-folders.playlists', PlaylistFolderPlaylistController::class)->except('destroy');
        Route::delete(
            'playlist-folders/{playlistFolder}/playlists',
            [PlaylistFolderPlaylistController::class, 'destroy']
        );

        // Playlist routes
        Route::apiResource('playlists', PlaylistController::class);
        Route::apiResource('playlists.songs', PlaylistSongController::class)->except('destroy');
        Route::delete('playlists/{playlist}/songs', [PlaylistSongController::class, 'destroy']);
        Route::post('playlists/{playlist}/songs/move', MovePlaylistSongsController::class);

        Route::get('genres/{genre}/songs', GenreSongController::class)->where('genre', '.*');
        Route::get('genres/{genre}/songs/random', FetchRandomSongsInGenreController::class)->where('genre', '.*');
        Route::apiResource('genres', GenreController::class)->where(['genre' => '.*']);

        Route::apiResource('users', UserController::class);

        // User and user profile routes
        Route::apiResource('user', UserController::class);
        Route::get('me', [ProfileController::class, 'show']);
        Route::put('me', [ProfileController::class, 'update']);
        Route::patch('me/preferences', UpdateUserPreferenceController::class);

        // Last.fm-related routes
        Route::post('lastfm/session-key', SetLastfmSessionKeyController::class);
        Route::delete('lastfm/disconnect', DisconnectFromLastfmController::class)->name('lastfm.disconnect');

        // YouTube-related routes
        if (YouTube::enabled()) {
            Route::get('youtube/search/song/{song}', SearchYouTubeController::class);
        }

        // Media information routes
        Route::get('albums/{album}/information', FetchAlbumInformationController::class);
        Route::get('artists/{artist}/information', FetchArtistInformationController::class);

        // Cover/image upload routes
        Route::put('albums/{album}/cover', UploadAlbumCoverController::class);
        Route::get('albums/{album}/thumbnail', FetchAlbumThumbnailController::class);
        Route::put('artists/{artist}/image', UploadArtistImageController::class);
        Route::put('playlists/{playlist}/cover', [PlaylistCoverController::class, 'update']);
        Route::delete('playlists/{playlist}/cover', [PlaylistCoverController::class, 'destroy']);
        // deprecated routes
        Route::put('album/{album}/cover', UploadAlbumCoverController::class);
        Route::get('album/{album}/thumbnail', FetchAlbumThumbnailController::class);
        Route::put('artist/{artist}/image', UploadArtistImageController::class);

        Route::get('search', ExcerptSearchController::class);
        Route::get('search/songs', SongSearchController::class);

        Route::post('invitations', [UserInvitationController::class, 'invite']);
        Route::delete('invitations', [UserInvitationController::class, 'revoke']);

        Route::put('songs/publicize', PublicizeSongsController::class);
        Route::put('songs/privatize', PrivatizeSongsController::class);

        // License routes
        Route::post('licenses/activate', ActivateLicenseController::class);

        // Playlist collaboration routes
        Route::post('playlists/{playlist}/collaborators/invite', CreatePlaylistCollaborationTokenController::class);
        Route::post('playlists/collaborators/accept', AcceptPlaylistCollaborationInviteController::class);
        Route::get('playlists/{playlist}/collaborators', [PlaylistCollaboratorController::class, 'index']);
        Route::delete('playlists/{playlist}/collaborators', [PlaylistCollaboratorController::class, 'destroy']);

        // Podcast routes
        Route::apiResource('podcasts', PodcastController::class);
        Route::get('episodes/{episode}', FetchEpisodeController::class);
        Route::apiResource('podcasts.episodes', PodcastEpisodeController::class);
        Route::delete('podcasts/{podcast}/subscriptions', UnsubscribeFromPodcastController::class);
    });

    // Object-storage (S3) routes
    Route::middleware('os.auth')->prefix('os/s3')->group(static function (): void {
        Route::post('song', [S3SongController::class, 'put'])->name('s3.song.put'); // we follow AWS's convention here.
        Route::delete('song', [S3SongController::class, 'remove'])->name('s3.song.remove'); // and here.
    });

    Route::get('demo/credits', FetchDemoCreditsController::class);
});
