<template>
  <span></span>
</template>

<script lang="ts" setup>
/**
 * Global event listeners (basically, those without a Vue instance access) go here.
 */
import isMobile from 'ismobilejs'
import { authService } from '@/services'
import { playlistFolderStore, playlistStore, preferenceStore, userStore } from '@/stores'
import { eventBus, forceReloadWindow, requireInjection } from '@/utils'
import { DialogBoxKey, MessageToasterKey, RouterKey } from '@/symbols'

const toaster = requireInjection(MessageToasterKey)
const dialog = requireInjection(DialogBoxKey)
const router = requireInjection(RouterKey)

eventBus.on({
  PLAYLIST_DELETE: async (playlist: Playlist) => {
    if (await dialog.value.confirm(`Delete the playlist "${playlist.name}"?`)) {
      await playlistStore.delete(playlist)
      toaster.value.success(`Playlist "${playlist.name}" deleted.`)
      router.go('home')
    }
  },

  PLAYLIST_FOLDER_DELETE: async (folder: PlaylistFolder) => {
    if (await dialog.value.confirm(`Delete the playlist folder "${folder.name}"?`)) {
      await playlistFolderStore.delete(folder)
      toaster.value.success(`Playlist folder "${folder.name}" deleted.`)
      router.go('home')
    }
  },

  /**
   * Log the current user out and reset the application state.
   */
  LOG_OUT: async () => {
    await userStore.logout()
    authService.destroy()
    forceReloadWindow()
  }
})

router.onRouteChanged(() => {
  // Hide the extra panel away if a main view is triggered on mobile.
  if (isMobile.phone) {
    preferenceStore.showExtraPanel = false
  }
})
</script>
