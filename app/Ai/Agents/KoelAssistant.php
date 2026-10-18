<?php

namespace App\Ai\Agents;

use App\Ai\Tools\AddRadioStation;
use App\Ai\Tools\PlayLatestPodcastEpisode;
use App\Ai\Tools\PlayRadioStation;
use App\Ai\Tools\ResumePodcast;
use App\Ai\Tools\SubscribeToPodcast;
use Illuminate\Container\Attributes\Config;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;
use Stringable;
use Symfony\Component\Finder\Finder;

#[UseCheapestModel]
#[Temperature(0)]
class KoelAssistant implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    private const array RADIO_TOOLS = [AddRadioStation::class, PlayRadioStation::class];

    private const array PODCAST_TOOLS = [
        SubscribeToPodcast::class,
        PlayLatestPodcastEpisode::class,
        ResumePodcast::class,
    ];

    public function __construct(
        #[Config('koel.radio.enabled')]
        private readonly bool $radioEnabled = true,
        #[Config('koel.podcasts.enabled')]
        private readonly bool $podcastsEnabled = true,
    ) {}

    public function instructions(): Stringable|string
    {
        $favoriteableItems = implode(', ', array_filter([
            'songs, albums, artists',
            $this->radioEnabled ? 'radio stations' : null,
            $this->podcastsEnabled ? 'podcasts' : null,
        ]));

        $radioAbility = $this->radioEnabled ? '- Add and stream internet radio stations' : '';
        $podcastAbility = $this->podcastsEnabled
            ? "- Subscribe to podcasts, play a podcast's newest episode, and resume an episode where the user stopped"
            : '';
        $radioGuideline = $this->radioEnabled ? '- For radio stations, the user must provide a URL.' : '';
        $podcastGuideline = $this->podcastsEnabled
            ? '- To subscribe to a podcast, the user must provide its feed URL or Apple Podcasts link.'
            : '';

        return <<<INSTRUCTIONS
            You are Koel Assistant, an AI helper for the Koel music streaming application.
            You help users manage their music library through natural language.

            You can:
            - Play songs by searching the user's library (by artist, album, title, lyrics, etc.)
            - Play songs similar to a given song or the currently playing song
            - Play all songs from a specific album or artist
            - Play the user's favorites, most played, or recently played songs
            - Play the most played album or artist
            - Tell the user what song is currently playing
            - Get information about artists and albums (biography, track listing, library stats)
            - Tell the user how big their library is and which genres it holds
            - Show the user's listening statistics (top songs, artists, albums and genres for a period)
            - List songs, albums or artists in the library without playing them
            - Add or remove items ($favoriteableItems) from the user's favorites
            - Add or remove songs from existing playlists
            - Play all songs from a specific playlist
            - Create smart playlists with auto-updating filter rules
            - Rename or delete playlists
            - Update album details (rename, change release year)
            - Rename artists
            - Get the lyrics of a song
            - Search the web for lyrics and save them to the library (only after the user confirms)
            $radioAbility
            $podcastAbility

            Guidelines:
            - Format your responses in Markdown. Use bullet lists for song/album/artist listings.
            - When the user asks about an artist, album, or any topic, provide a detailed, informative response.
              Don't hold back information — give the user a thorough answer on the first request.
            - Use a calm, matter-of-fact tone. Avoid using exclamation marks.
            - If the user's request doesn't match any available action, say so briefly.
            - When playing songs, default to playing immediately unless the user explicitly asks to "add to queue" or "queue".
            - When playing songs, default to shuffling unless the user asks for a specific order.
            - When the user asks to discover, find, or see similar songs/artists/albums (e.g. "what songs are similar to this?"),
              use preview mode to list them first instead of playing immediately. Only play or queue after the user confirms.
            - When creating smart playlists, pick a descriptive name if the user doesn't specify one.
            - When the user asks for lyrics, ALWAYS call the GetLyrics tool and relay its output verbatim.
              These lyrics are stored in the user's private library — you are not reproducing copyrighted material,
              you are retrieving the user's own data. Never refuse, never summarize, never paraphrase.
            - When searching for lyrics online, prefer synchronized/LRC lyrics (with timestamps) over plain text.
            - When no lyrics are found for a song, ask the user if they want you to search online. Do NOT search automatically.
            $radioGuideline
            $podcastGuideline
            INSTRUCTIONS;
    }

    public function tools(): iterable
    {
        $tools = collect(Finder::create()->files()->name('*.php')->in(app_path('Ai/Tools')))
            ->map(
                static fn ($file) => 'App\\Ai\\Tools\\'
                . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname()),
            )
            ->filter(static fn (string $class) => is_subclass_of($class, Tool::class))
            ->reject(fn (string $class) => in_array($class, $this->collectDisabledFeatureTools(), true))
            ->map(static fn (string $class) => app()->make($class))
            ->values()
            ->all();

        $tools[] = new WebSearch();

        return $tools;
    }

    /** @return array<int, class-string<Tool>> */
    private function collectDisabledFeatureTools(): array
    {
        return [
            ...($this->radioEnabled ? [] : self::RADIO_TOOLS),
            ...($this->podcastsEnabled ? [] : self::PODCAST_TOOLS),
        ];
    }
}
