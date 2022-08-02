<template>
  <div class="thumbnail-stack" :class="layout" :style="{ backgroundImage: `url(${defaultCover})` }">
    <span
      v-for="thumbnail in displayedThumbnails"
      :style="{ backgroundImage: `url(${thumbnail}`}"
      data-testid="thumbnail"
    />
  </div>
</template>

<script lang="ts" setup>
import { take } from 'lodash'
import { computed, toRefs } from 'vue'
import { defaultCover } from '@/utils'

const props = defineProps<{ thumbnails: string[] }>()
const { thumbnails } = toRefs(props)

const displayedThumbnails = computed(() => {
  return thumbnails.value.length == 0
    ? [defaultCover]
    : (thumbnails.value.length < 4 ? [thumbnails.value[0]] : take(thumbnails.value, 4)).map(url => url || defaultCover)
})

const layout = computed<'single' | 'tiles'>(() => displayedThumbnails.value.length < 4 ? 'single' : 'tiles')
</script>

<style lang="scss" scoped>
.thumbnail-stack {
  aspect-ratio: 1/1;
  display: grid;
  overflow: hidden;
  background-size: cover;
  background-repeat: no-repeat;

  &.tiles {
    grid-template-columns: 1fr 1fr;
  }

  span {
    display: block;
    will-change: transform; // fix anti-aliasing problem with background images
    width: 100%;
    height: 100%;
    background-size: cover;
    background-repeat: no-repeat;
  }
}
</style>
