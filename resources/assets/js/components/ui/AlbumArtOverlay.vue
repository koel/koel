<template>
  <div :style="{ backgroundImage: thumbnailUrl ? `url(${thumbnailUrl})` : 'none' }" data-testid="album-art-overlay" />
</template>

<script lang="ts" setup>
import { ref, toRefs, watchEffect } from 'vue'
import { albumStore } from '@/stores'

const props = defineProps<{ album: number }>()
const { album } = toRefs(props)

const thumbnailUrl = ref<String | null>(null)

watchEffect(async () => {
  try {
    thumbnailUrl.value = await albumStore.fetchThumbnail(album.value)
  } catch (e) {
    thumbnailUrl.value = null
  }
})
</script>

<style scoped>
div {
  position: fixed;
  opacity: .1;
  z-index: 10000;
  overflow: hidden;
  background-size: cover;
  background-position: center;
  pointer-events: none;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}
</style>
