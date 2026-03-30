<template>
  <button
    :style="{ backgroundImage: `url(${defaultCover})` }"
    class="thumbnail group relative w-full aspect-square bg-no-repeat bg-cover bg-center overflow-hidden rounded-md active:scale-95"
    data-testid="radio-station-card-thumbnail"
    :title="`Play/pause ${station.name}`"
    @click.prevent="emit('clicked')"
  >
    <img alt="Logo" :src="station.logo || defaultCover" class="w-full aspect-square object-cover" loading="lazy" />
    <span class="absolute top-0 left-0 w-full h-full group-hover:bg-black/40 no-hover:bg-black/40 z-10" />
    <PlayIcon :playing="station.playback_state === 'Playing'" />
  </button>
</template>

<script setup lang="ts">
import { toRefs } from 'vue'
import { useBranding } from '@/composables/useBranding'

import PlayIcon from '@/components/ui/PlayIcon.vue'

const props = defineProps<{ station: RadioStation }>()
const emit = defineEmits<{ (e: 'clicked'): void }>()

const { station } = toRefs(props)
const { cover: defaultCover } = useBranding()
</script>
