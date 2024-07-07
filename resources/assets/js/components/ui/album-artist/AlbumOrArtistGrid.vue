<template>
  <div :class="`as-${viewMode}`" class="grid gap-5 p-6">
    <slot />
  </div>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

const props = withDefaults(defineProps<{ viewMode?: ArtistAlbumViewMode }>(), {
  viewMode: 'thumbnails'
})

const { viewMode } = toRefs(props)
</script>

<style lang="postcss" scoped>
div {
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}

div.as-list {
  @apply gap-x-4 gap-y-3 content-start;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
}
</style>
