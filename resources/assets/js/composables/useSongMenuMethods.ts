import { favoriteStore, playlistStore, queueStore } from '@/stores'

/**
 * Includes the methods trigger-able on a song (context) menu.
 * Each component including this mixin must have a `songs` array as either data, prop, or computed.
 */
export const useSongMenuMethods = (songs: Song[], close: TAnyFunction) => {
  const queueSongsAfterCurrent = () => {
    queueStore.queueAfterCurrent(songs)
    close()
  }

  const queueSongsToBottom = () => {
    queueStore.queue(songs)
    close()
  }

  const queueSongsToTop = () => {
    queueStore.queueToTop(songs)
    close()
  }

  const addSongsToFavorite = async () => {
    await favoriteStore.like(songs)
    close()
  }

  const addSongsToExistingPlaylist = async (playlist: Playlist) => {
    await playlistStore.addSongs(playlist, songs)
    close()
  }

  return {
    queueSongsAfterCurrent,
    queueSongsToBottom,
    queueSongsToTop,
    addSongsToFavorite,
    addSongsToExistingPlaylist
  }
}
