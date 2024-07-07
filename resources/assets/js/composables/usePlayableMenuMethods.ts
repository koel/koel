import { Ref } from 'vue'
import { favoriteStore, queueStore } from '@/stores'
import { usePlaylistManagement } from '@/composables'
import { eventBus } from '@/utils'

export const usePlayableMenuMethods = (playables: Ref<Playable[]>, close: Closure) => {
  const { addToPlaylist } = usePlaylistManagement()

  const trigger = async (cb: Closure) => {
    close()
    await cb()
  }

  return {
    queueAfterCurrent: () => trigger(() => queueStore.queueAfterCurrent(playables.value)),
    queueToBottom: () => trigger(() => queueStore.queue(playables.value)),
    queueToTop: () => trigger(() => queueStore.queueToTop(playables.value)),
    addToFavorites: () => trigger(() => favoriteStore.like(playables.value)),
    addToExistingPlaylist: (playlist: Playlist) => trigger(() => addToPlaylist(playlist, playables.value)),
    addToNewPlaylist: () => trigger(() => eventBus.emit('MODAL_SHOW_CREATE_PLAYLIST_FORM', null, playables.value))
  }
}
