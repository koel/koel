<template>
  <slot />
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useErrorHandler, useOverlay } from '@/composables'
import { commonStore, preferenceStore as preferences } from '@/stores'
import { socketListener, socketService, uploadService } from '@/services'

const { showOverlay, hideOverlay } = useOverlay()

const emits = defineEmits<{
  (e: 'success'): void,
  (e: 'error', err: unknown): void,
}>()

/**
 * Request for notification permission if it's not provided and the user is OK with notifications.
 */
const requestNotificationPermission = async () => {
  if (preferences.show_now_playing_notification && window.Notification && window.Notification.permission !== 'granted') {
    preferences.show_now_playing_notification = await window.Notification.requestPermission() === 'denied'
  }
}

onMounted(async () => {
  showOverlay({ message: 'Just a little patience…' })

  try {
    await commonStore.init()

    await requestNotificationPermission()

    window.addEventListener('beforeunload', (e: BeforeUnloadEvent) => {
      if (uploadService.shouldWarnUponWindowUnload() || preferences.confirm_before_closing) {
        e.preventDefault()
        e.returnValue = ''
      }
    })

    await socketService.init() && socketListener.listen()

    emits('success')
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error)
    emits('error', error)
  } finally {
    hideOverlay()
  }
})
</script>

<style scoped lang="postcss">

</style>
