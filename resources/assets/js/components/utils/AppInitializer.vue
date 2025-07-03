<template>
  <slot />
</template>

<script lang="ts" setup>
import { onMounted } from 'vue'
import { useAuthorization } from '@/composables/useAuthorization'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useOverlay } from '@/composables/useOverlay'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { socketListener } from '@/services/socketListener'
import { socketService } from '@/services/socketService'
import { uploadService } from '@/services/uploadService'
import { broadcastSubscriber } from '@/services/broadcastSubscriber'

const emits = defineEmits<{
  (e: 'success'): void
  (e: 'error', err: unknown): void
}>()

const { showOverlay, hideOverlay } = useOverlay()
const { currentUser } = useAuthorization()

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

    broadcastSubscriber.init(currentUser.value.id)
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
