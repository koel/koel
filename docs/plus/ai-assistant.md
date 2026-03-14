---
description: Using the AI assistant to control music playback, manage playlists, and explore your library with natural language.
---

# AI Assistant

Koel Plus comes with an AI-powered assistant that lets you interact with your music library using natural language.
Instead of navigating through menus, you can simply type what you want in plain English.

:::danger Disclaimer
The AI assistant and its tools are provided as-is for personal use.
Koel and its developers assume no legal responsibility for how these tools are used or for the content they generate, fetch, or modify.
It is the user's sole responsibility to ensure that their usage complies with applicable laws and regulations in their jurisdiction.
:::

:::warning Important
As with any AI-powered feature, the assistant may occasionally misunderstand your request, produce unexpected results,
or fail to find what you're looking for. Natural language is inherently ambiguous, and the assistant's responses
depend on the underlying AI model. Always verify important actions (e.g., deleting playlists) and don't hesitate
to rephrase your request if the result isn't what you expected.
:::

## Setup

To enable the AI assistant, you need to configure an AI provider. Set the following environment variables in your `.env` file:

```dotenv
AI_ENABLED=true
AI_PROVIDER=openai
OPENAI_API_KEY=your-openai-api-key
```

Or, if you prefer Anthropic:

```dotenv
AI_ENABLED=true
AI_PROVIDER=anthropic
ANTHROPIC_API_KEY=your-anthropic-api-key
```

`AI_PROVIDER` must match one of the provider keys defined in `config/ai.php` (e.g., `openai`, `anthropic`, `gemini`, `ollama`).

The AI assistant is powered by [Laravel's AI SDK](https://laravel.com/docs/12.x/ai-sdk),
which supports a wide range of providers including Anthropic, Gemini, DeepSeek, Mistral, Groq, Ollama, and more.
For the full list of supported providers and their configuration options,
refer to the [Laravel AI SDK documentation](https://laravel.com/docs/12.x/ai-sdk).

## Using the Assistant

Open the AI assistant by pressing <kbd>/</kbd> or clicking the sparkle button in the interface.
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
- "Find the lyrics for this song if there are none"

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

## Known Limitations

### Lyrics Display

When asked to show lyrics, the assistant retrieves them from your library and attempts to relay them in the response.
However, some AI providers may refuse to display lyrics due to copyright restrictions built into their models.
If this happens, try a different AI provider — behavior varies between providers and models.
The lyrics are still stored in your library and can always be viewed via the side sheet's Lyrics tab.

## Conversation Memory

The AI assistant remembers the context of your current conversation session.
This means you can have follow-up requests like:

- "Play some jazz" → "Actually, add those to the queue instead"
- "Tell me about Radiohead" → "Play their most popular songs"

The conversation resets when you close and reopen the assistant.
