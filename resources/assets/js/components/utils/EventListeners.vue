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
  PLAYLIST_DELETE (playlist: Playlist) {
    const destroy = async () => {
      await playlistStore.delete(playlist)
      alerts.success(`Deleted playlist "${playlist.name}."`)
      router.go('home')
    }

    if (!playlist.is_smart && !playlist.songs.length) {
      destroy()
    } else {
      alerts.confirm(`Delete the playlist "${playlist.name}"?`, destroy)
    }
  },

  /**
   * Log the current user out and reset the application state.
   */
  async LOG_OUT () {
    await userStore.logout()
    authService.destroy()
    forceReloadWindow()
  },

  'KOEL_READY': () => router.init(),

  /**
   * Hide the panel away if a main view is triggered on mobile.
   */
  'LOAD_MAIN_CONTENT': () => isMobile.phone && (preferenceStore.showExtraPanel = false)
})
</script>
