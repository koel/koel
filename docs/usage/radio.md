# Radio

You can also add and listen to radio stations with Koel.
To manage radio stations, click on the "Radio" link from the sidebar to go to the "Radio Stations" page.
Here you will find the list of available radio stations — those you have added and those made public by other users.
You can mark radio stations as favorites and edit or delete the stations you own.

![Radio Stations screen](../assets/img/interface/radio-stations.avif)

::: info Information
When adding or editing a radio station, Koel checks the URL to ensure it is a valid radio stream,
i.e., it must have a content type of `audio/*`. An error will be thrown if it's not the case.
:::

## Streaming Radio Stations

Streaming radio stations is not much different from streaming songs: You basically click the play button to start listening.
Most of the interface elements you are familiar with, such as volume control, equalizer, and visualizer, work the same way.
However, there are a couple of differences:

* Radio stations do not use a queue, and therefore there are no next or previous buttons. Repeat and shuffle modes are also not available.
* You cannot rewind, fast-forward, or seek a radio stream.
* You cannot add radio stations to playlists.
* Inapplicable interface elements such as the side sheet (artist, albums, lyrics, etc.) will be hidden.
* As of current, Koel doesn't maintain the "state" of radio stations. This means when you open Koel again, it will revert to the queued songs (or podcast episodes).
  This limitation applies to both the web interface and the [mobile apps](../mobile-apps.md) (which don't support radio stations yet).
