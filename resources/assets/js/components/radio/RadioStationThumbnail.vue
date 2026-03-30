<template>
  <button
    :style="{ backgroundImage: `url(${defaultCover})` }"
    class="thumbnail relative w-full aspect-square bg-no-repeat bg-cover bg-center overflow-hidden rounded-md active:scale-95"
    data-testid="radio-station-card-thumbnail"
    :title="`Play/pause ${station.name}`"
    @click.prevent="emit('clicked')"
  >
    <img alt="Logo" :src="station.logo || defaultCover" class="w-full aspect-square object-cover" loading="lazy" />
    <span class="absolute top-0 left-0 w-full h-full group-hover:bg-black/40 no-hover:bg-black/40 z-10" />
    <PlayIcon>
      <Icon v-if="station.playback_state === 'Playing'" :icon="faPause" class="text-k-highlight-fg" size="lg" />
      <Icon v-else :icon="faPlay" class="ml-0.5 text-k-highlight-fg" size="lg" />
    </PlayIcon>
  </button>
</template>

<script setup lang="ts">
import { toRefs } from 'vue'
import { faPause, faPlay } from '@fortawesome/free-solid-svg-icons'
import { useBranding } from '@/composables/useBranding'

import PlayIcon from '@/components/ui/PlayIcon.vue'

const props = defineProps<{ station: RadioStation }>()
const emit = defineEmits<{ (e: 'clicked'): void }>()

const { station } = toRefs(props)
const { cover: defaultCover } = useBranding()
</script>
