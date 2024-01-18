/**
 * Global event listeners (basically, those without a Vue instance access) go here.
 */

import { defineComponent, onMounted } from 'vue'
import { authService } from '@/services'
import { playlistFolderStore, playlistStore } from '@/stores'
import { eventBus, forceReloadWindow } from '@/utils'
import { useDialogBox, useMessageToaster, useRouter } from '@/composables'

export const GlobalEventListeners = defineComponent({
  setup (props, { slots }) {
    let toastSuccess: ReturnType<typeof useMessageToaster>['toastSuccess']
    let showConfirmDialog: ReturnType<typeof useDialogBox>['showConfirmDialog']
    let go: ReturnType<typeof useRouter>['go']

    onMounted(() => {
      toastSuccess = useMessageToaster().toastSuccess
      showConfirmDialog = useDialogBox().showConfirmDialog
      go = useRouter().go
    })

    eventBus.on('PLAYLIST_DELETE', async playlist => {
      if (await showConfirmDialog(`Delete the playlist "${playlist.name}"?`)) {
        await playlistStore.delete(playlist)
        toastSuccess(`Playlist "${playlist.name}" deleted.`)
        go('home')
      }
    }).on('PLAYLIST_FOLDER_DELETE', async folder => {
      if (await showConfirmDialog(`Delete the playlist folder "${folder.name}"?`)) {
        await playlistFolderStore.delete(folder)
        toastSuccess(`Playlist folder "${folder.name}" deleted.`)
        go('home')
      }
    }).on('LOG_OUT', async () => {
      await authService.logout()
      forceReloadWindow()
    })

    return () => slots.default?.()
  }
})
