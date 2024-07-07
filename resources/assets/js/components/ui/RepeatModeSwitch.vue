<template>
  <button
    v-koel-tooltip.top
    :class="{ active: mode !== 'NO_REPEAT' }"
    :title="`Change repeat mode (current: ${readableMode})`"
    class="opacity-30"
    data-testid="repeat-mode-switch"
    type="button"
    @click.prevent="changeMode"
  >
    <Repeat1 v-if="mode === 'REPEAT_ONE'" :size="16" />
    <Repeat v-else :size="16" />
  </button>
</template>

<script lang="ts" setup>
import { Repeat, Repeat1 } from 'lucide-vue-next'
import { computed, toRef } from 'vue'
import { playbackService } from '@/services'
import { preferenceStore } from '@/stores'

const mode = toRef(preferenceStore.state, 'repeat_mode')

const readableMode = computed(() => mode.value
  .split('_')
  .map(part => part[0].toUpperCase() + part.substring(1).toLowerCase())
  .join(' ')
)

const changeMode = () => playbackService.rotateRepeatMode()
</script>

<style lang="postcss" scoped>
.active {
  @apply opacity-70;
}
</style>
