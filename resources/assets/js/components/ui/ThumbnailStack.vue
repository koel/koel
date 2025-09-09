<template>
  <article
    :class="layout"
    :style="{ backgroundImage: `url(${defaultCover})` }"
    class="thumbnail-stack flex-1 aspect-square overflow-hidden grid bg-cover bg-no-repeat"
  >
    <span
      v-for="thumbnail in displayedThumbnails"
      :key="thumbnail"
      :style="{ backgroundImage: `url(${thumbnail}` }"
      class="block w-full h-full bg-cover bg-no-repeat"
      data-testid="thumbnail"
    />
  </article>
</template>

<script lang="ts" setup>
import { take } from 'lodash'
import { computed, ref, toRefs, watch } from 'vue'
import defaultCover from '@/../img/covers/default.svg'

const props = defineProps<{ thumbnails: string[] }>()
const { thumbnails } = toRefs(props)

const defaultBackgroundImage = `url(${defaultCover})`
const displayedThumbnails = ref<string[]>([])

watch(thumbnails, () => {
  if (thumbnails.value.length === 0) {
    displayedThumbnails.value = [defaultCover]
  } else {
    displayedThumbnails.value = take(thumbnails.value, thumbnails.value.length < 4 ? 1 : 4)
      .map(url => url || defaultCover)
  }
}, { immediate: true })

const layout = computed(() => displayedThumbnails.value.length < 4 ? 'single' : 'tiles')
</script>

<style lang="postcss" scoped>
article {
  background-image: v-bind(defaultBackgroundImage);

  &.tiles {
    @apply grid-cols-2;
  }
}
</style>
