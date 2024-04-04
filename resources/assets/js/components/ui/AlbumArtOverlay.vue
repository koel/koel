<template>
  <div
    :style="{ backgroundImage: thumbnailUrl ? `url(${thumbnailUrl})` : 'none' }"
    class="pointer-events-none fixed z-[1000] overflow-hidden opacity-10 bg-cover bg-center top-0 left-0 h-full w-full"
    data-testid="album-art-overlay"
  />
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
