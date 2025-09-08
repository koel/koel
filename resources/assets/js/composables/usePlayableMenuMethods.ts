import type { Ref } from 'vue'
import { queueStore } from '@/stores/queueStore'
import { eventBus } from '@/utils/eventBus'
import { usePlaylistContentManagement } from '@/composables/usePlaylistContentManagement'
import { playableStore } from '@/stores/playableStore'

export const usePlayableMenuMethods = (playables: Ref<Playable[]>, close: Closure) => {
  const { addToPlaylist } = usePlaylistContentManagement()

  const trigger = async (cb: Closure) => {
    close()
    await cb()
  }

  return {
    queueAfterCurrent: () => trigger(() => queueStore.queueAfterCurrent(playables.value)),
    queueToBottom: () => trigger(() => queueStore.queue(playables.value)),
    queueToTop: () => trigger(() => queueStore.queueToTop(playables.value)),
    addToFavorites: () => trigger(() => playableStore.favorite(playables.value)),
    removeFromFavorites: () => trigger(() => playableStore.undoFavorite(playables.value)),
    removeFromQueue: () => trigger(() => queueStore.unqueue(playables.value)),
    addToExistingPlaylist: (playlist: Playlist) => trigger(() => addToPlaylist(playlist, playables.value)),
    addToNewPlaylist: () => trigger(() => eventBus.emit('MODAL_SHOW_CREATE_PLAYLIST_FORM', null, playables.value)),
  }
}
