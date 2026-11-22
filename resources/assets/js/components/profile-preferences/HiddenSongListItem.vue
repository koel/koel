<template>
  <li class="flex items-center gap-3 py-2.5">
    <div class="flex-1 min-w-0">
      <p class="truncate text-k-fg">{{ song.title }}</p>
      <p class="truncate text-sm text-k-fg-50">{{ song.artist_name }}</p>
    </div>
    <Btn size="small" variant="ghost" bordered @click.prevent="unhide">Unhide</Btn>
  </li>
</template>

<script lang="ts" setup>
import { playableStore } from '@/stores/playableStore'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'

import Btn from '@/components/ui/form/Btn.vue'

const props = defineProps<{ song: Song }>()
const emit = defineEmits<{ (e: 'unhidden', song: Song): void }>()

const { toastSuccess } = useMessageToaster()
const { handleHttpError } = useErrorHandler()

const unhide = async () => {
  try {
    await playableStore.unhideSongs([props.song])
    toastSuccess('Song unhidden.')
    emit('unhidden', props.song)
  } catch (error: unknown) {
    handleHttpError(error)
  }
}
</script>
