<template>
  <slot />
</template>

<script lang="ts" setup>
import { onMounted } from 'vue'
import { useRouter } from '@/composables/useRouter'
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
</script>
