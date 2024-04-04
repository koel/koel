<template>
  <article
    :class="layout"
    :style="{ backgroundImage: `url(${defaultCover})` }"
    class="thumbnail-stack aspect-square overflow-hidden grid bg-cover bg-no-repeat"
  >
    <span
      v-for="thumbnail in displayedThumbnails"
      :key="thumbnail"
      :style="{ backgroundImage: `url(${thumbnail}`}"
      class="block will-change-transform w-full h-full bg-cover bg-no-repeat"
      data-testid="thumbnail"
    />
  </article>
</template>

<script lang="ts" setup>
import { take } from 'lodash'
import { computed, toRefs } from 'vue'
import { defaultCover } from '@/utils'

const defaultBackgroundImage = `url(${ defaultCover })`

const props = defineProps<{ thumbnails: string[] }>()
const { thumbnails } = toRefs(props)

const displayedThumbnails = computed(() => {
  return thumbnails.value.length == 0
    ? [defaultCover]
    : (thumbnails.value.length < 4 ? [thumbnails.value[0]] : take(thumbnails.value, 4)).map(url => url || defaultCover)
})

const layout = computed<'single' | 'tiles'>(() => displayedThumbnails.value.length < 4 ? 'single' : 'tiles')
</script>

<style lang="postcss" scoped>
article {
  background-image: v-bind(defaultBackgroundImage);

  &.tiles {
    @apply grid-cols-2;
  }
}
</style>
