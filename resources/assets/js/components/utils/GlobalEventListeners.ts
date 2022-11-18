/**
 * Global event listeners (basically, those without a Vue instance access) go here.
 */

import { defineComponent } from 'vue'
import { authService } from '@/services'
import { playlistFolderStore, playlistStore, userStore } from '@/stores'
import { eventBus, forceReloadWindow, requireInjection } from '@/utils'
import { RouterKey } from '@/symbols'
import { useDialogBox, useMessageToaster } from '@/composables'

export const GlobalEventListeners = defineComponent({
  setup (props, { slots }) {
    const { toastSuccess } = useMessageToaster()
    const { showConfirmDialog } = useDialogBox()
    const router = requireInjection(RouterKey)

    eventBus.on('PLAYLIST_DELETE', async playlist => {
      if (await showConfirmDialog(`Delete the playlist "${playlist.name}"?`)) {
        await playlistStore.delete(playlist)
        toastSuccess(`Playlist "${playlist.name}" deleted.`)
        router.go('home')
      }
    }).on('PLAYLIST_FOLDER_DELETE', async folder => {
      if (await showConfirmDialog(`Delete the playlist folder "${folder.name}"?`)) {
        await playlistFolderStore.delete(folder)
        toastSuccess(`Playlist folder "${folder.name}" deleted.`)
        router.go('home')
      }
    }).on('LOG_OUT', async () => {
      await userStore.logout()
      authService.destroy()
      forceReloadWindow()
    })

    return () => slots.default?.()
  }
})
