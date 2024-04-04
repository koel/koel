<template>
  <article class="flex-1 flex flex-col items-center justify-around">
    <div
      :style="{ backgroundImage: `url(${song.album_cover || defaultCover})` }"
      class="cover my-0 mx-auto w-[calc(70vw_+_4px)] aspect-square rounded-full border-2 border-solid
      border-k-text-primary bg-center bg-cover bg-k-bg-secondary"
    />
    <div class="w-full flex flex-col justify-around">
      <div>
        <p class="text-[6vmin] font-bold mx-auto mb-4">{{ song.title }}</p>
        <p class="text-[5vmin] mb-2 opacity-50">{{ song.artist_name }}</p>
        <p class="text-[4vmin] opacity-50">{{ song.album_name }}</p>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { toRefs } from 'vue'
import { defaultCover } from '@/utils'

const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)
</script>


<style scoped lang="postcss">
p {
  @apply max-w-[90%] mx-auto overflow-hidden text-ellipsis whitespace-nowrap leading-[1.3];
}
</style>
