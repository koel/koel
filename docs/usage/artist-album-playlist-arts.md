---
description: How Koel fetches and displays artwork from Spotify, Last.fm, and MusicBrainz, with support for custom uploads.
---

# Artist, Album, and Playlist Arts

If [integrated](../service-integrations) Koel will attempt to fetch artists' images and album arts from Spotify,
Last.fm, or MusicBrainz/Wikipedia whenever a song is played. You can upload custom images for artists, albums, and
playlists via the corresponding edit form.

If there is no image available for an artist or album, Koel will use a default image (the Koel bird logo).
For a playlist, a random "thumbnail stack" will be generated and used based on the tracks.

Newer versions of Koel also provide a [command](../cli-commands.md#koel-fetch-artwork) to fetch missing artist and
album arts.

## Descriptions

The Information tab of an artist or album shows a description found online, when an integration is set up. To write your
own, open the artist's or album's edit form and switch to the Description tab. Your text replaces the online one, and
stays until you change it. Leave it empty to go back to the online description.
