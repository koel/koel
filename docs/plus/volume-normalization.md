---
description: Evening out loudness between songs and showing each song's waveform behind the player.
---

# Volume Normalization & Waveforms

With volume normalization, Koel turns loud songs down and quiet songs up, so a quiet old record and a loud new one
rarely make you reach for the volume. A quiet song is only turned up as far as it can go without clipping. Koel also
shows each song's waveform behind the player, so you can see the quiet and loud parts at a glance.

## Requirements

Both features need [FFmpeg](https://ffmpeg.org/) installed on your server. Koel finds it automatically; if it doesn't,
set `FFMPEG_PATH` in your `.env` file to the full path of the `ffmpeg` binary.

## Setup

Turn on audio analysis in your `.env` file:

```dotenv
ANALYZE_AUDIO_ON_SCAN=true
```

From then on, Koel measures the loudness and draws the waveform of every song as soon as it's scanned or uploaded. This
typically takes about a second per song. Songs scanned before you turned it on aren't analyzed until they are scanned
again, and play as before.

## Turning It Off

Normalization is on by default. Each user can turn it off under _Profile & Preferences → Preferences_ with the _Play
songs at about the same volume_ switch.
