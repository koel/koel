---
description: Connecting Subsonic-compatible apps to your Koel library with your personal API key.
---

# Subsonic Clients

Koel speaks the [Subsonic / OpenSubsonic API](https://opensubsonic.netlify.app/), so you can connect any Subsonic-compatible app to your Koel library and stream your music from wherever that app runs.

<CaptionedImage :src="feishin" alt="Feishin playing music from a Koel library">Feishin streaming a Koel library over the Subsonic API</CaptionedImage>

## Get Your API Key

1. Go to your profile.
2. Switch to the **Subsonic** tab.
3. Reveal or copy the key.

The key is created for you the first time you open this tab and stays the same until you regenerate it.

## Connect a Client

In your Subsonic client, point the server URL at your Koel installation — the same URL you use in the browser — and enter:

* **Username** — your Koel email address
* **Password** (or **API key**, if the client offers it) — your Subsonic API key

The Subsonic API key replaces your Koel password here — do **not** enter your actual Koel account password. Your Koel password is never used by Subsonic clients.

Your Koel library will appear in the client right after you save the connection.

## Compatible Clients

Any client that speaks Subsonic or OpenSubsonic will work. A few popular ones:

* [Feishin](https://github.com/jeffvli/feishin) — macOS, Windows, Linux
* [Amperfy](https://github.com/BLeeEZ/amperfy) — macOS, iOS, iPadOS
* [Symfonium](https://symfonium.app/) — Android
* [Tempo](https://github.com/CappielloAntonio/tempo) — Android
* [substreamer](https://substreamer.org/) — iOS, Android, Web
* [play:Sub](https://michaelsapps.dk/playsubapp/) — iOS

## Regenerate Your Key

If you think your key has been exposed, regenerate it from the same Subsonic tab. Click **Regenerate Key** and confirm.

::: warning
Regenerating the key invalidates the old one straight away. Any client still using it will stop working until you update its credentials.
:::

## What Koel Supports

Koel implements the OpenSubsonic API for browsing, streaming, search, playlists, favorites, ratings, internet radio stations, podcasts, and lyrics.

Sharing, bookmarks, chat, and play-queue sync endpoints are not implemented.

<script lang="ts" setup>
import feishin from '../assets/img/interface/feishin.avif'
</script>
