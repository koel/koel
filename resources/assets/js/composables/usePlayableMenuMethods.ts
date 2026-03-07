import type { Ref } from 'vue'
import { queueStore } from '@/stores/queueStore'
import { defineAsyncComponent } from '@/utils/helpers'
import { usePlaylistContentManagement } from '@/composables/usePlaylistContentManagement'
import { useModal } from '@/composables/useModal'
import { playableStore } from '@/stores/playableStore'

const CreatePlaylistForm = defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistForm.vue'))

export const usePlayableMenuMethods = (playables: Ref<Playable[]>, close: Closure) => {
  const { addToPlaylist } = usePlaylistContentManagement()
  const { openModal } = useModal()

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
    addToNewPlaylist: () =>
      trigger(() =>
        openModal<'CREATE_PLAYLIST_FORM'>(CreatePlaylistForm, { folder: null, playables: playables.value }),
      ),
  }
}
