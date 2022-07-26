import { Ref } from 'vue'
import { favoriteStore, playlistStore, queueStore } from '@/stores'
import { pluralize, requireInjection } from '@/utils'
import { DialogBoxKey, MessageToasterKey } from '@/symbols'

export const useSongMenuMethods = (songs: Ref<Song[]>, close: Closure) => {
  const toaster = requireInjection(MessageToasterKey)
  const dialog = requireInjection(DialogBoxKey)

  const queueSongsAfterCurrent = () => {
    close()
    queueStore.queueAfterCurrent(songs.value)
  }

  const queueSongsToBottom = () => {
    close()
    queueStore.queue(songs.value)
  }

  const queueSongsToTop = () => {
    close()
    queueStore.queueToTop(songs.value)
  }

  const addSongsToFavorite = async () => {
    close()
    await favoriteStore.like(songs.value)
  }

  const addSongsToExistingPlaylist = async (playlist: Playlist) => {
    close()

    try {
      await playlistStore.addSongs(playlist, songs.value)
      toaster.value.success(`Added ${pluralize(songs.value.length, 'song')} into "${playlist.name}."`)
    } catch (error) {
      dialog.value.error('Something went wrong. Please try again.', 'Error')
    }
  }

  return {
    queueSongsAfterCurrent,
    queueSongsToBottom,
    queueSongsToTop,
    addSongsToFavorite,
    addSongsToExistingPlaylist
  }
}
