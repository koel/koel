import { Ref } from 'vue'
import { favoriteStore, queueStore } from '@/stores'
import { usePlaylistManagement } from '@/composables'
import { eventBus } from '@/utils'

export const useSongMenuMethods = (songs: Ref<Song[]>, close: Closure) => {
  const { addSongsToPlaylist } = usePlaylistManagement()

  const trigger = async (cb: Closure) => {
    close()
    await cb()
  }

  return {
    queueSongsAfterCurrent: () => trigger(() => queueStore.queueAfterCurrent(songs.value)),
    queueSongsToBottom: () => trigger(() => queueStore.queue(songs.value)),
    queueSongsToTop: () => trigger(() => queueStore.queueToTop(songs.value)),
    addSongsToFavorites: () => trigger(() => favoriteStore.like(songs.value)),
    addSongsToExistingPlaylist: (playlist: Playlist) => trigger(() => addSongsToPlaylist(playlist, songs.value)),
    addSongsToNewPlaylist: () => trigger(() => eventBus.emit('MODAL_SHOW_CREATE_PLAYLIST_FORM', null, songs.value))
  }
}
