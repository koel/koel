import { ref, Ref } from 'vue'
import { favoriteStore, playlistStore, queueStore } from '@/stores'
import { alerts, pluralize } from '@/utils'

export const useSongMenuMethods = (songs: Ref<Song[]>, close: Closure) => {
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
    await playlistStore.addSongs(ref(playlist), songs.value)
    alerts.success(`Added ${pluralize(songs.value.length, 'song')} into "${playlist.name}."`)
  }

  return {
    queueSongsAfterCurrent,
    queueSongsToBottom,
    queueSongsToTop,
    addSongsToFavorite,
    addSongsToExistingPlaylist
  }
}
