---
description: See what you've been listening to in Koel — top songs, artists, albums, and genres, your listening time, when you listen most, and how big your library is.
---

# Statistics

Click **Statistics** in the sidebar to see what's in your library and what you've been listening to.

The **Storage** section shows how many songs, albums, and artists you can play. If podcasts are enabled, it also shows the podcasts you subscribe to and their episodes. If radio is enabled, it shows the radio stations you can listen to. These numbers count everything you can play and follow your choice to include public media or not.

You may also see the total size of all song files in the library. Podcast episodes and transcoded files don't count. Older songs may count as zero until you run [`php artisan koel:fill-file-sizes`](../cli-commands#koel-fill-file-sizes).

In the **Listening** section, pick a period: the last 7 days, 30 days, 12 months, or all time. For that period, you'll see:

- how many plays, how much listening time, and how many different songs and artists;
- your plays over time;
- which days of the week and which hours of the day you listen most, in your own time zone;
- your top 12 songs and genres, and your top 10 artists and albums.

A song counts as played once you've listened to a quarter of it, the same way Koel counts plays everywhere else. Plays from [Subsonic clients](./subsonic) count too. Podcast episodes don't.

Koel starts counting plays for your statistics once you're on a version that has this page, so older listening doesn't show up here. Listening time adds up the full length of each song you played.
