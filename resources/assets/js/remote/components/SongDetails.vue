<template>
  <article class="flex-1 flex flex-col items-center justify-around">
    <div
      :style="{ backgroundImage: `url(${image || defaultCover})` }"
      class="cover my-0 mx-auto w-[calc(70vw_+_4px)] aspect-square rounded-full border-2 border-solid
      border-k-text-primary bg-center bg-cover bg-k-bg-secondary"
    />
    <div class="w-full flex flex-col justify-around">
      <div>
        <p class="text-[6vmin] font-bold mx-auto mb-4">{{ song.title }}</p>
        <p class="text-[5vmin] mb-2 opacity-50">{{ artist }}</p>
        <p class="text-[4vmin] opacity-50">{{ album }}</p>
      </div>
    </div>
  </article>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { defaultCover, getPlayableProp } from '@/utils'

const props = defineProps<{ song: Playable }>()
const { song } = toRefs(props)

const image = computed(() => getPlayableProp(song.value, 'album_cover', 'episode_image'))
const artist = computed(() => getPlayableProp(song.value, 'artist_name', 'podcast_author'))
const album = computed(() => getPlayableProp(song.value, 'album_name', 'podcast_title'))
</script>


<style lang="postcss" scoped>
p {
  @apply max-w-[90%] mx-auto overflow-hidden text-ellipsis whitespace-nowrap leading-[1.3];
}
</style>
