<template>
  <Btn size="small" variant="ghost" rounded :disabled="refreshing" @click.prevent="refreshShelf">
    <Icon :icon="faRotateRight" :class="{ 'animate-spin': refreshing }" />
    <span class="sr-only">Refresh</span>
  </Btn>
</template>

<script lang="ts" setup>
import { faRotateRight } from '@fortawesome/free-solid-svg-icons'
import { useErrorHandler } from '@/composables/useErrorHandler'

import Btn from '@/components/ui/form/Btn.vue'

const props = defineProps<{ refresh: () => Promise<unknown> }>()

const refreshing = defineModel<boolean>('refreshing', { default: false })

const refreshShelf = async () => {
  refreshing.value = true

  try {
    await props.refresh()
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error)
  } finally {
    refreshing.value = false
  }
}
</script>
