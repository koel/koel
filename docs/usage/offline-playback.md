---
description: Caching songs for offline playback using Service Workers, managing offline storage, and limitations.
---

# Offline Playback

Koel allows you to cache songs for offline playback directly from your browser. This is especially useful when
you're on the go or have an unreliable internet connection.

:::info Information
Offline playback requires a modern browser with Service Worker support.
The feature is not available in older browsers or when Service Workers are disabled.
:::

## Making Songs Available Offline

To cache a song for offline playback, right-click on it and select "Make Available Offline" from the context menu.
You can also select multiple songs, or right-click on an entire album or playlist to cache all songs at once.
The caching progress will be displayed in real time.

Once cached, an offline indicator will appear next to the song, signifying that it can be played without an internet connection.

## Viewing Offline Songs

All songs that have been cached for offline playback can be found on the "Available Offline" screen, accessible from the sidebar.
This screen lists all cached songs along with their metadata.

## Removing Offline Cache

To remove a song from the offline cache, right-click on it and select "Remove Offline Version" from the context menu.
As with caching, you can bulk-remove by selecting multiple songs or right-clicking on an album or playlist.

## Storage Management

You can view and manage your offline storage usage from the [Preferences screen](./profile-preferences#preferences).
A progress bar shows how much storage space is being used by cached songs.
To clear all cached songs at once, click the "Clear All" button.

## Limitations

* Offline playback is only available for songs, not for radio stations or podcast episodes.
* The amount of storage available depends on your browser and device. Koel will display the current usage and quota
  in the Preferences screen.
* Songs cached in one browser are not available in another browser or device.
