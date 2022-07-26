<template>
  <span></span>
</template>

<script lang="ts" setup>
/**
 * Global event listeners (basically, those without a Vue instance access) go here.
 */
import isMobile from 'ismobilejs'
import router from '@/router'
import { authService } from '@/services'
import { playlistStore, preferenceStore, userStore } from '@/stores'
import { eventBus, forceReloadWindow, requireInjection } from '@/utils'
import { DialogBoxKey } from '@/symbols'

const dialog = requireInjection(DialogBoxKey)

eventBus.on({
  'PLAYLIST_DELETE': async (playlist: Playlist) => {
    if (await dialog.value.confirm(`Are you sure you want to delete "${playlist.name}"?`, 'Delete Playlist')) {
      await playlistStore.delete(playlist)
      router.go('home')
    }
  },

  /**
   * Log the current user out and reset the application state.
   */
  'LOG_OUT': async () => {
    await userStore.logout()
    authService.destroy()
    forceReloadWindow()
  },

  'KOEL_READY': () => router.resolveRoute(),

  /**
   * Hide the panel away if a main view is triggered on mobile.
   */
  'LOAD_MAIN_CONTENT': () => isMobile.phone && (preferenceStore.showExtraPanel = false)
})
</script>
