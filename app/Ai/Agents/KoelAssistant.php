<?php

namespace App\Ai\Agents;

use App\Ai\AiAssistantResult;
use App\Ai\Tools\AddRadioStation;
use App\Ai\Tools\CreateSmartPlaylist;
use App\Ai\Tools\GetAlbumInfo;
use App\Ai\Tools\GetArtistInfo;
use App\Ai\Tools\GetCurrentSong;
use App\Ai\Tools\PlayAlbum;
use App\Ai\Tools\PlayArtist;
use App\Ai\Tools\PlayFavorites;
use App\Ai\Tools\PlayMostPlayed;
use App\Ai\Tools\PlayRadioStation;
use App\Ai\Tools\PlayRecentlyAdded;
use App\Ai\Tools\PlayRecentlyAddedAlbum;
use App\Ai\Tools\PlayRecentlyAddedArtist;
use App\Ai\Tools\PlayRecentlyPlayed;
use App\Ai\Tools\PlaySimilarSongs;
use App\Ai\Tools\PlaySongs;
use App\Ai\Tools\PlaySongsByGenre;
use App\Ai\Tools\PlaySongsByLyrics;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\RadioStationRepository;
use App\Repositories\SongRepository;
use App\Services\EncyclopediaService;
use App\Services\PlaylistService;
use App\Services\RadioService;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
#[Temperature(0)]
class KoelAssistant implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function __construct(
        private readonly User $user,
        private readonly AiAssistantResult $result,
        private readonly SongRepository $songRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly RadioStationRepository $radioStationRepository,
        private readonly EncyclopediaService $encyclopediaService,
        private readonly PlaylistService $playlistService,
        private readonly RadioService $radioService,
        private readonly ?string $currentSongId = null,
        private readonly ?string $currentRadioStationId = null,
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            You are Koel Assistant, an AI helper for the Koel music streaming application.
            You help users manage their music library through natural language.

            You can:
            - Play songs by searching the user's library (by artist, album, title, lyrics, etc.)
            - Play songs similar to a given song or the currently playing song
            - Play all songs from a specific album or artist
            - Play the user's favorites, most played, or recently played songs
            - Tell the user what song is currently playing
            - Get information about artists and albums (biography, track listing, library stats)
            - Create smart playlists with auto-updating filter rules
            - Add and stream internet radio stations

            Guidelines:
            - Be concise in your responses — one or two sentences max.
            - If the user's request doesn't match any available action, say so briefly.
            - When playing songs, default to shuffling unless the user asks for a specific order.
            - When creating smart playlists, pick a descriptive name if the user doesn't specify one.
            - For radio stations, the user must provide a URL.
            INSTRUCTIONS;
    }

    public function tools(): iterable
    {
        return [
            new PlaySongs($this->user, $this->result, $this->songRepository),
            new PlaySongsByGenre($this->user, $this->result, $this->songRepository),
            new PlaySongsByLyrics($this->user, $this->result, $this->songRepository),
            new PlaySimilarSongs($this->user, $this->result, $this->songRepository, $this->currentSongId),
            new PlayAlbum($this->user, $this->result, $this->albumRepository, $this->songRepository),
            new PlayArtist($this->user, $this->result, $this->artistRepository, $this->songRepository),
            new PlayFavorites($this->user, $this->result, $this->songRepository),
            new PlayMostPlayed($this->user, $this->result, $this->songRepository),
            new PlayRecentlyPlayed($this->user, $this->result, $this->songRepository),
            new PlayRecentlyAdded($this->user, $this->result, $this->songRepository),
            new PlayRecentlyAddedAlbum($this->user, $this->result, $this->albumRepository, $this->songRepository),
            new PlayRecentlyAddedArtist($this->user, $this->result, $this->songRepository),
            new GetCurrentSong(
                $this->user,
                $this->songRepository,
                $this->radioStationRepository,
                $this->encyclopediaService,
                $this->currentSongId,
                $this->currentRadioStationId,
            ),
            new GetArtistInfo($this->user, $this->artistRepository, $this->songRepository, $this->encyclopediaService),
            new GetAlbumInfo($this->user, $this->albumRepository, $this->songRepository, $this->encyclopediaService),
            new PlayRadioStation($this->user, $this->result, $this->radioStationRepository),
            new CreateSmartPlaylist($this->user, $this->result, $this->playlistService),
            new AddRadioStation($this->user, $this->result, $this->radioService),
        ];
    }
}
