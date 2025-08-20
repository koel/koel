<template>
  <button
    :style="{ backgroundImage: `url(${defaultCover})` }"
    class="thumbnail relative w-full aspect-square bg-no-repeat bg-cover bg-center overflow-hidden rounded-md active:scale-95"
    data-testid="radio-station-card-thumbnail"
    :title="`Play/pause ${station.name}`"
    @click.prevent="emit('clicked')"
  >
    <img
      alt="Logo"
      :src="station.logo || defaultCover"
      class="w-full aspect-square object-cover"
      loading="lazy"
    >
    <span class="absolute top-0 left-0 w-full h-full group-hover:bg-black/40 no-hover:bg-black/40 z-10" />
    <span
      class="play-icon absolute flex opacity-0 no-hover:opacity-100 items-center justify-center w-[32px] aspect-square rounded-full top-1/2
        left-1/2 -translate-x-1/2 -translate-y-1/2 bg-k-highlight group-hover:opacity-100 duration-500 transition z-20"
    >
      <Icon v-if="station.playback_state === 'Playing'" :icon="faPause" class="text-white" size="lg" />
      <Icon v-else :icon="faPlay" class="ml-0.5 text-white" size="lg" />
    </span>
  </button>
</template>

<script setup lang="ts">
import { toRefs } from 'vue'
import { faPause, faPlay } from '@fortawesome/free-solid-svg-icons'
import defaultCover from '@/../img/covers/default.svg'

const props = defineProps<{ station: RadioStation }>()
const emit = defineEmits<{ (e: 'clicked'): void }>()

const { station } = toRefs(props)
</script>
