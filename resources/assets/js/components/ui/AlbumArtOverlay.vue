<template>
  <div :style="{ backgroundImage: thumbnailUrl ? `url(${thumbnailUrl})` : 'none' }" data-testid="album-art-overlay"/>
</template>

<script lang="ts" setup>
import { ref, toRefs, watchEffect } from 'vue'
import { albumStore } from '@/stores'

const props = defineProps<{ song: Song | null }>()
const { song } = toRefs(props)

const thumbnailUrl = ref<String | null>(null)

watchEffect(async () => {
  if (song.value) {
    try {
      thumbnailUrl.value = await albumStore.getThumbnail(song.value.album)
    } catch (e) {
      thumbnailUrl.value = null
    }
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
