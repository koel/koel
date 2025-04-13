<template>
  <article
    :class="[layout, { playing: isPlaying }]"
    :style="{ backgroundImage: `url(${defaultCover})` }"
    class="thumbnail-stack aspect-square overflow-hidden grid bg-cover bg-no-repeat thumbnail-stack"
  >
    <span
      v-for="thumbnail in displayedThumbnails"
      :key="thumbnail"
      :style="{ backgroundImage: `url(${thumbnail}` }"
      class=" album-thumb block will-change-transform w-full h-full bg-cover bg-no-repeat"
      data-testid="thumbnail"
    />
  </article>
</template>

<script lang="ts" setup>
import { take } from 'lodash'
import { computed, toRefs } from 'vue'
import defaultCover from '@/../img/covers/default.svg'

const props = defineProps<{ thumbnails: string[], songs?: object }>()

const defaultBackgroundImage = `url(${defaultCover})`

const { thumbnails, songs } = toRefs(props)

const displayedThumbnails = computed(() => {
  return thumbnails.value.length === 0
    ? [defaultCover]
    : (thumbnails.value.length < 4 ? [thumbnails.value[0]] : take(thumbnails.value, 4)).map(url => url || defaultCover)
})

const layout = computed<'single' | 'tiles'>(() => displayedThumbnails.value.length < 4 ? 'single' : 'tiles')

const isPlaying = computed(() => {
  return songs.value?.some(song => song.playback_state === 'Playing') || false
})
</script>

<style lang="postcss" scoped>
article {
  background-image: v-bind(defaultBackgroundImage);

  &.tiles {
    @apply grid-cols-2;
  }
  &.playing .album-thumb {
    @apply motion-reduce:animate-none;
    animation: spin 30s linear infinite;
  }
}
@keyframes spin {
  100% {
    transform: rotate(360deg);
  }
}
</style>
