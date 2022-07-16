<template>
  <div class="thumbnail-stack" :class="layout">
    <span v-for="thumbnail in displayedThumbnails" :style="{ backgroundImage: `url(${thumbnail}`}"/>
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

const layout = computed<'mono' | 'tiles'>(() => displayedThumbnails.value.length < 4 ? 'mono' : 'tiles')
</script>

<style lang="scss" scoped>
.thumbnail-stack {
  aspect-ratio: 1/1;
  display: grid;
  overflow: hidden;

  &.tiles {
    grid-template-columns: 1fr 1fr;
  }

  span {
    display: block;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-repeat: no-repeat;
  }
}
</style>
