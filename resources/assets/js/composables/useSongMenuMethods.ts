import { Ref } from 'vue'
import { favoriteStore, playlistStore, queueStore } from '@/stores'
import { alerts, pluralize } from '@/utils'

/**
 * Includes the methods trigger-able on a song (context) menu.
 * Each component including this mixin must have a `songs` array as either data, prop, or computed.
 */
export const useSongMenuMethods = (songs: Ref<Song[]>, close: TAnyFunction) => {
  const queueSongsAfterCurrent = () => {
    queueStore.queueAfterCurrent(songs.value)
    close()
  }

  const queueSongsToBottom = () => {
    queueStore.queue(songs.value)
    close()
  }

  const queueSongsToTop = () => {
    queueStore.queueToTop(songs.value)
    close()
  }

  const addSongsToFavorite = async () => {
    await favoriteStore.like(songs.value)
    close()
  }

  const addSongsToExistingPlaylist = async (playlist: Playlist) => {
    await playlistStore.addSongs(playlist, songs.value)
    alerts.success(`Added ${pluralize(songs.value.length, 'song')} into "${playlist.name}."`)
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
