<template>
  <slot />
</template>

<script lang="ts" setup>
import { onMounted } from 'vue'
import { useRouter } from '@/composables/useRouter'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { eventBus } from '@/utils/eventBus'
import { authService } from '@/services/authService'
import { forceReloadWindow } from '@/utils/helpers'

let go: ReturnType<typeof useRouter>['go']

onMounted(() => {
  go = useRouter().go
})

eventBus.on('LOG_OUT', async () => {
  await authService.logout()
  go('/')
  forceReloadWindow()
})

eventBus.on('OFFLINE_PLAYBACK_BLOCKED', () => {
  useMessageToaster().toastWarning('This song is not available offline.')
})
</script>
