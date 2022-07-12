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
import { alerts, eventBus, forceReloadWindow } from '@/utils'

eventBus.on({
  'PLAYLIST_DELETE': (playlist: Playlist) => {
    alerts.confirm(`Delete the playlist "${playlist.name}"?`, async () => {
      await playlistStore.delete(playlist)
      alerts.success(`Deleted playlist "${playlist.name}."`)
      router.go('home')
    })
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
