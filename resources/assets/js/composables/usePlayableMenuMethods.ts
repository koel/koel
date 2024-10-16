import type { Ref } from 'vue'
import { favoriteStore } from '@/stores/favoriteStore'
import { queueStore } from '@/stores/queueStore'
import { eventBus } from '@/utils/eventBus'
import { usePlaylistManagement } from '@/composables/usePlaylistManagement'

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
    addToNewPlaylist: () => trigger(() => eventBus.emit('MODAL_SHOW_CREATE_PLAYLIST_FORM', null, playables.value)),
  }
}
