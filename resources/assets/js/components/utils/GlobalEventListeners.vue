<template>
  <slot />
</template>

<script lang="ts" setup>
import { onMounted } from 'vue'
import { useRouter } from '@/composables/useRouter'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useDialogBox } from '@/composables/useDialogBox'
import { eventBus } from '@/utils/eventBus'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import { authService } from '@/services/authService'
import { forceReloadWindow } from '@/utils/helpers'

let toastSuccess: ReturnType<typeof useMessageToaster>['toastSuccess']
let showConfirmDialog: ReturnType<typeof useDialogBox>['showConfirmDialog']
let go: ReturnType<typeof useRouter>['go']
let url: ReturnType<typeof useRouter>['url']

onMounted(() => {
  toastSuccess = useMessageToaster().toastSuccess
  showConfirmDialog = useDialogBox().showConfirmDialog
  go = useRouter().go
  url = useRouter().url
})

eventBus.on('PLAYLIST_DELETE', async playlist => {
  if (await showConfirmDialog(`Delete the playlist "${playlist.name}"?`)) {
    await playlistStore.delete(playlist)
    toastSuccess(`Playlist "${playlist.name}" deleted.`)
    go(url('home'))
  }
}).on('PLAYLIST_FOLDER_DELETE', async folder => {
  if (await showConfirmDialog(`Delete the playlist folder "${folder.name}"?`)) {
    await playlistFolderStore.delete(folder)
    toastSuccess(`Playlist folder "${folder.name}" deleted.`)
    go(url('home'))
  }
}).on('LOG_OUT', async () => {
  await authService.logout()
  forceReloadWindow()
})
</script>
