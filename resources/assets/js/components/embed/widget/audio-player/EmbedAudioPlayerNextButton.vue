<template>
  <button
    :class="playable || 'opacity-50 pointer-events-none'"
    :disabled="!playable"
    class="w-10 transition aspect-square rounded-full border border-px border-white/20 hover:scale-110"
    type="button"
    @click.prevent="emit('clicked')"
  >
    <span class="sr-only">Play next</span>
    <Icon :icon="faStepForward" />
  </button>
</template>

<script setup lang="ts">
import { faStepForward } from '@fortawesome/free-solid-svg-icons'
import { toRefs } from 'vue'

const props = defineProps<{ playable?: Playable | null }>()
const emit = defineEmits<{ (e: 'clicked'): void }>()

const { playable } = toRefs(props)
</script>
