<template>
  <slot />
</template>

<script setup lang="ts">
import { useDialogBox, useMessageToaster, useRouter } from '@/composables'
import { onMounted } from 'vue'
import { eventBus, forceReloadWindow } from '@/utils'
import { playlistFolderStore, playlistStore } from '@/stores'
import { authService } from '@/services'

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
</script>
