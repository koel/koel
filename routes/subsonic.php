<?php

use App\Http\Controllers\Subsonic\CreateInternetRadioStationController;
use App\Http\Controllers\Subsonic\CreatePlaylistController;
use App\Http\Controllers\Subsonic\CreatePodcastChannelController;
use App\Http\Controllers\Subsonic\DeleteInternetRadioStationController;
use App\Http\Controllers\Subsonic\DeletePlaylistController;
use App\Http\Controllers\Subsonic\DeletePodcastChannelController;
use App\Http\Controllers\Subsonic\DownloadController;
use App\Http\Controllers\Subsonic\GetAlbumController;
use App\Http\Controllers\Subsonic\GetAlbumInfo2Controller;
use App\Http\Controllers\Subsonic\GetAlbumInfoController;
use App\Http\Controllers\Subsonic\GetAlbumList2Controller;
use App\Http\Controllers\Subsonic\GetAlbumListController;
use App\Http\Controllers\Subsonic\GetArtistController;
use App\Http\Controllers\Subsonic\GetArtistInfo2Controller;
use App\Http\Controllers\Subsonic\GetArtistInfoController;
use App\Http\Controllers\Subsonic\GetArtistsController;
use App\Http\Controllers\Subsonic\GetAvatarController;
use App\Http\Controllers\Subsonic\GetCoverArtController;
use App\Http\Controllers\Subsonic\GetGenresController;
use App\Http\Controllers\Subsonic\GetIndexesController;
use App\Http\Controllers\Subsonic\GetInternetRadioStationsController;
use App\Http\Controllers\Subsonic\GetLicenseController;
use App\Http\Controllers\Subsonic\GetLyricsBySongIdController;
use App\Http\Controllers\Subsonic\GetLyricsController;
use App\Http\Controllers\Subsonic\GetMusicDirectoryController;
use App\Http\Controllers\Subsonic\GetMusicFoldersController;
use App\Http\Controllers\Subsonic\GetNewestPodcastsController;
use App\Http\Controllers\Subsonic\GetNowPlayingController;
use App\Http\Controllers\Subsonic\GetOpenSubsonicExtensionsController;
use App\Http\Controllers\Subsonic\GetPlaylistController;
use App\Http\Controllers\Subsonic\GetPlaylistsController;
use App\Http\Controllers\Subsonic\GetPlayQueueController;
use App\Http\Controllers\Subsonic\GetPodcastsController;
use App\Http\Controllers\Subsonic\GetRandomSongsController;
use App\Http\Controllers\Subsonic\GetSongController;
use App\Http\Controllers\Subsonic\GetSongsByGenreController;
use App\Http\Controllers\Subsonic\GetStarred2Controller;
use App\Http\Controllers\Subsonic\GetStarredController;
use App\Http\Controllers\Subsonic\GetUserController;
use App\Http\Controllers\Subsonic\PingController;
use App\Http\Controllers\Subsonic\RefreshPodcastsController;
use App\Http\Controllers\Subsonic\SavePlayQueueController;
use App\Http\Controllers\Subsonic\ScrobbleController;
use App\Http\Controllers\Subsonic\Search2Controller;
use App\Http\Controllers\Subsonic\Search3Controller;
use App\Http\Controllers\Subsonic\SetRatingController;
use App\Http\Controllers\Subsonic\StarController;
use App\Http\Controllers\Subsonic\StreamController;
use App\Http\Controllers\Subsonic\UnstarController;
use App\Http\Controllers\Subsonic\UpdateInternetRadioStationController;
use App\Http\Controllers\Subsonic\UpdatePlaylistController;
use App\Http\Middleware\AuthenticateSubsonicRequests;
use App\Http\Middleware\NormalizeSubsonicArrayParams;
use Illuminate\Support\Facades\Route;

$endpoints = [
    'ping' => PingController::class,
    'getLicense' => GetLicenseController::class,
    'getMusicFolders' => GetMusicFoldersController::class,
    'getArtists' => GetArtistsController::class,
    'getIndexes' => GetIndexesController::class,
    'getMusicDirectory' => GetMusicDirectoryController::class,
    'getInternetRadioStations' => GetInternetRadioStationsController::class,
    'createInternetRadioStation' => CreateInternetRadioStationController::class,
    'updateInternetRadioStation' => UpdateInternetRadioStationController::class,
    'deleteInternetRadioStation' => DeleteInternetRadioStationController::class,
    'getArtist' => GetArtistController::class,
    'getAlbum' => GetAlbumController::class,
    'getSong' => GetSongController::class,
    'getGenres' => GetGenresController::class,
    'search2' => Search2Controller::class,
    'search3' => Search3Controller::class,
    'getAlbumList' => GetAlbumListController::class,
    'getAlbumList2' => GetAlbumList2Controller::class,
    'stream' => StreamController::class,
    'getCoverArt' => GetCoverArtController::class,
    'getPlaylists' => GetPlaylistsController::class,
    'getPlaylist' => GetPlaylistController::class,
    'createPlaylist' => CreatePlaylistController::class,
    'updatePlaylist' => UpdatePlaylistController::class,
    'deletePlaylist' => DeletePlaylistController::class,
    'scrobble' => ScrobbleController::class,
    'star' => StarController::class,
    'unstar' => UnstarController::class,
    'setRating' => SetRatingController::class,
    'getStarred' => GetStarredController::class,
    'getStarred2' => GetStarred2Controller::class,
    'getRandomSongs' => GetRandomSongsController::class,
    'getNowPlaying' => GetNowPlayingController::class,
    'getUser' => GetUserController::class,
    'getAvatar' => GetAvatarController::class,
    'getOpenSubsonicExtensions' => GetOpenSubsonicExtensionsController::class,
    'download' => DownloadController::class,
    'getSongsByGenre' => GetSongsByGenreController::class,
    'getLyrics' => GetLyricsController::class,
    'getLyricsBySongId' => GetLyricsBySongIdController::class,
    'getArtistInfo' => GetArtistInfoController::class,
    'getArtistInfo2' => GetArtistInfo2Controller::class,
    'getAlbumInfo' => GetAlbumInfoController::class,
    'getAlbumInfo2' => GetAlbumInfo2Controller::class,
    'getPodcasts' => GetPodcastsController::class,
    'getNewestPodcasts' => GetNewestPodcastsController::class,
    'refreshPodcasts' => RefreshPodcastsController::class,
    'createPodcastChannel' => CreatePodcastChannelController::class,
    'deletePodcastChannel' => DeletePodcastChannelController::class,
    'getPlayQueue' => GetPlayQueueController::class,
    'savePlayQueue' => SavePlayQueueController::class,
];

Route::prefix('rest')
    ->middleware([NormalizeSubsonicArrayParams::class, AuthenticateSubsonicRequests::class])
    ->group(static function () use ($endpoints): void {
        foreach ($endpoints as $endpoint => $controller) {
            Route::match(['get', 'post'], "{$endpoint}{format?}", $controller)->where('format', '\.view');
        }
    });
