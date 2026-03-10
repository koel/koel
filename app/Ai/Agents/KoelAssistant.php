<?php

namespace App\Ai\Agents;

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
            - Play the most played album or artist
            - Tell the user what song is currently playing
            - Get information about artists and albums (biography, track listing, library stats)
            - Add or remove items (songs, albums, artists, radio stations, podcasts) from the user's favorites
            - Add or remove songs from existing playlists
            - Play all songs from a specific playlist
            - Create smart playlists with auto-updating filter rules
            - Rename or delete playlists
            - Get the lyrics of a song
            - Search the web for lyrics when a song has none, and save them to the library
            - Add and stream internet radio stations

            Guidelines:
            - Be concise in your responses — one or two sentences max.
            - Use a calm, matter-of-fact tone. Avoid using exclamation marks.
            - If the user's request doesn't match any available action, say so briefly.
            - When playing songs, default to playing immediately unless the user explicitly asks to "add to queue" or "queue".
            - When playing songs, default to shuffling unless the user asks for a specific order.
            - When creating smart playlists, pick a descriptive name if the user doesn't specify one.
            - For radio stations, the user must provide a URL.
            INSTRUCTIONS;
    }

    public function tools(): iterable
    {
        $tools = collect(
            Finder::create()
                ->files()
                ->name('*.php')
                ->in(app_path('Ai/Tools')),
        )
            ->map(
                static fn ($file) => 'App\\Ai\\Tools\\'
                . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname()),
            )
            ->filter(static fn (string $class) => is_subclass_of($class, Tool::class))
            ->map(static fn (string $class) => app()->make($class))
            ->values()
            ->all();

        $tools[] = new WebSearch();

        return $tools;
    }
}
