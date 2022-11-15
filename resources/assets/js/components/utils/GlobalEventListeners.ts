/**
 * Global event listeners (basically, those without a Vue instance access) go here.
 */

import { defineComponent } from 'vue'
import { authService } from '@/services'
import { playlistFolderStore, playlistStore, userStore } from '@/stores'
import { eventBus, forceReloadWindow, requireInjection } from '@/utils'
import { DialogBoxKey, MessageToasterKey, RouterKey } from '@/symbols'

export const GlobalEventListeners = defineComponent({
  setup (props, { slots }) {
    const toaster = requireInjection(MessageToasterKey)
    const dialog = requireInjection(DialogBoxKey)
    const router = requireInjection(RouterKey)

    eventBus.on('PLAYLIST_DELETE', async playlist => {
      if (await dialog.value.confirm(`Delete the playlist "${playlist.name}"?`)) {
        await playlistStore.delete(playlist)
        toaster.value.success(`Playlist "${playlist.name}" deleted.`)
        router.go('home')
      }
    }).on('PLAYLIST_FOLDER_DELETE', async folder => {
      if (await dialog.value.confirm(`Delete the playlist folder "${folder.name}"?`)) {
        await playlistFolderStore.delete(folder)
        toaster.value.success(`Playlist folder "${folder.name}" deleted.`)
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
