<template>
  <button
    class="w-8 transition aspect-square rounded bg-k-highlight border border-px border-k-highlight hover:scale-110"
    type="button"
    @click.prevent="emit('clicked')"
  >
    <Icon v-if="playing" :icon="faPause" data-testid="icon-pause" />
    <Icon v-else :icon="faPlay" data-testid="icon-play" />
    <span class="sr-only">{{ playing ? 'Pause' : 'Play/Resume' }}</span>
  </button>
</template>

<script setup lang="ts">
import { faPause, faPlay } from '@fortawesome/free-solid-svg-icons'
import { computed, toRefs } from 'vue'

const props = defineProps<{ playable?: Playable }>()
const emit = defineEmits<{ (e: 'clicked'): void }>()

const { playable } = toRefs(props)

const playing = computed(() => playable.value?.playback_state === 'Playing')
</script>
