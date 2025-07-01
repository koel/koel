# Service Integrations

To further enhance your music experience, Koel supports several 3rd-party service integrations: MusicBrainz, Last.fm,
Spotify, and YouTube.

:::tip Note
Koel prefers MusicBrainz/Wikipedia for artist and album information, and Spotify for artist images and album arts.
:::

## MusicBrainz (+Wikipedia)

[MusicBrainz](https://musicbrainz.org/) is a community-maintained open music encyclopedia that collects music metadata
and makes it available to the public. Koel uses MusicBrainz (with cross-reference to Wikipedia) to retrieve artist and
album information, artist images, and album arts.

You don't have anything to do to enable this integration, as it is enabled by default. However, you can disable it by
explicitly setting `USE_MUSICBRAINZ` to `false` in `.env`.

Do note that MusicBrainz [rate-limits](https://musicbrainz.org/doc/MusicBrainz_API/Rate_Limiting) its API.
Koel tries to play nice by caching the data it retrieves and using a conformed user agent, which you can customize
via the `MUSICBRAINZ_USER_AGENT` variable in `.env`. The default user agent is `Koel/<current-version> (<app-url>)`.

## Last.fm

Connecting Koel to Last.fm will instruct Koel to retrieve artist and album information from Last.fm as well as support
scrobbling. To enable the connection:

1. [Create a Last.fm API account](https://www.last.fm/api/account/create). In the **Callback URL** field, fill in `https://<your-koel-host>/api/lastfm/callback` (though this is not used).
2. Populate the two variables `LASTFM_API_KEY` and `LASTFM_API_SECRET` in `.env` with the credentials grabbed from step 1. This enables Koel to retrieve media information from Last.fm.
3. To enable scrobbling, go to `https://<your-koel-host>/#/profile` and click the **Connect** button under Last.fm Integration. This connection is per-user, i.e. each user can connect their own Last.fm account.

## Spotify

Integration with Spotify allows Koel to fetch more metadata like album arts and artist images. To enable the integration:

1. Register for a developer account and create an app in [Spotify dashboard](https://developer.spotify.com/dashboard/)
2. Populate `SPOTIFY_CLIENT_ID` and `SPOTIFY_CLIENT_SECRET` in `.env` with the credentials from step 1.

## YouTube

With YouTube integration, whenever a song is played, Koel will search YouTube for related videos and display them in
the sidebar for you to watch without leaving Koel. The only thing you need to do is fill in `.env` with your
`YOUTUBE_API_KEY`, which can be obtained via the following:

1. [Create a new Google Project](https://console.developers.google.com/)
2. From the project's Dashboard, click “ENABLE API” and make sure “YouTube Data API v3” is enabled
3. From the project's Credentials, click Create credentials → API Key → Server key

:::tip Limitations
YouTube integration is always disabled on mobile due to OS restrictions. Also, you interact with the videos via YouTube
controls. Koel's equalizer, volume, seeker, play/pause buttons, etc., doesn't have an effect on the videos.
:::
