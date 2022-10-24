import { Ref } from 'vue'
import { favoriteStore, queueStore } from '@/stores'
import { usePlaylistManagement } from '@/composables'

export const useSongMenuMethods = (songs: Ref<Song[]>, close: Closure) => {
  const { addSongsToPlaylist } = usePlaylistManagement()

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
    await addSongsToPlaylist(playlist, songs.value)
  }

  return {
    queueSongsAfterCurrent,
    queueSongsToBottom,
    queueSongsToTop,
    addSongsToFavorite,
    addSongsToExistingPlaylist
  }
}
