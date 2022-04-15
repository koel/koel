import { favoriteStore, playlistStore, queueStore } from '@/stores'

/**
 * Includes the methods trigger-able on a song (context) menu.
 * Each component including this mixin must have a `songs` array as either data, prop, or computed.
 * Note that for some components, some methods here may not be applicable, or overridden,
 * for example close() and open().
 */
export const useSongMenuMethods = (close: Function) => {
  const props = defineProps<{ songs: Song[] }>()

  const queueSongsAfterCurrent = () => {
    queueStore.queueAfterCurrent(props.songs)
    close()
  }

  const queueSongsToBottom = () => {
    queueStore.queue(props.songs)
    close()
  }

  const queueSongsToTop = () => {
    queueStore.queueToTop(props.songs)
    close()
  }

  const addSongsToFavorite = async () => {
    await favoriteStore.like(props.songs)
    close()
  }

  const addSongsToExistingPlaylist = async (playlist: Playlist) => {
    await playlistStore.addSongs(playlist, props.songs)
    close()
  }

  return {
    props,
    queueSongsAfterCurrent,
    queueSongsToBottom,
    queueSongsToTop,
    addSongsToFavorite,
    addSongsToExistingPlaylist
  }
}
