---
description: Using the AI assistant to control music playback, manage playlists, and explore your library with natural language.
---

# AI Assistant

Koel Plus comes with an AI-powered assistant that lets you interact with your music library using natural language.
Instead of navigating through menus, you can simply type what you want in plain English.

## Setup

To enable the AI assistant, you need to configure an AI provider. Set the following environment variables in your `.env` file:

```dotenv
AI_ENABLED=true
OPENAI_API_KEY=your-openai-api-key
```

Alternatively, you can use Anthropic (Claude) instead of OpenAI:

```dotenv
AI_ENABLED=true
ANTHROPIC_API_KEY=your-anthropic-api-key
```

Additional providers (Gemini, Ollama, etc.) can be configured in `config/ai.php`.

## Using the Assistant

Open the AI assistant by pressing <kbd>Cmd/Ctrl+K</kbd> or clicking the sparkle button in the interface.
Type your request in natural language and press Enter.

The assistant understands a wide range of requests. Here are some examples to get you started:

### Playing Music

- "Play the album Abbey Road"
- "Play some jazz"
- "Play Pink Floyd"
- "Play my favorite songs"
- "Play my most played tracks"
- "Replay what I listened to recently"
- "Play my Chill Vibes playlist"
- "Add some jazz to the queue"
- "Play the song that goes 'Is this the real life, is this just fantasy'"
- "Play songs similar to this"
- "Play my most listened-to album"
- "Play my top artist"

### Library Information

- "What song is playing right now?"
- "Tell me about Radiohead"
- "Tell me about the album Dark Side of the Moon"
- "Show me the lyrics of this song"

### Managing Playlists

- "Create a smart playlist with Dance songs from the 90s"
- "Add this to my Road Trip playlist"
- "Remove this song from my Workout playlist"
- "Rename my playlist to Summer Hits"
- "Delete my old workout playlist"

### Favorites

- "Add this song to my favorites"
- "Remove this song from my favorites"
- "Add this album to my favorites"
- "Favorite the artist Radiohead"

### Radio Stations

- "Stream some classical music" (if you have a matching radio station)

:::tip
The assistant is context-aware. If a song is currently playing, you can refer to it with "this song" or "this"
without having to specify the exact title.
:::

## Conversation Memory

The AI assistant remembers the context of your current conversation session.
This means you can have follow-up requests like:

- "Play some jazz" → "Actually, add those to the queue instead"
- "Tell me about Radiohead" → "Play their most popular songs"

The conversation resets when you close and reopen the assistant.
