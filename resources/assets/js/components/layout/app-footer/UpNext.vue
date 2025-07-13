<template>
  <article
    v-if="playable"
    class="fixed z-[99] right-[5vw] top-[4.5rem] flex bg-k-bg-primary border border-px border-k-border"
  >
    <span :style="{ backgroundImage: `url(${defaultCover})` }">
      <img :src alt="Cover image" class="w-[96px] aspect-square object-cover" loading="lazy">
    </span>
    <main class="px-5 py-4 min-w-80 max-w-96 flex flex-col justify-between overflow-hidden">
      <h4 class="uppercase text-k-text-secondary">Up Next</h4>
      <p
        class="text-k-text-primary text-xl overflow-hidden whitespace-nowrap overflow-ellipsis"
      >
        {{ playable.title }}
      </p>
      <p class="text-k-text-secondary overflow-hidden whitespace-nowrap overflow-ellipsis">{{ author }}</p>
    </main>
  </article>
</template>

<script setup lang="ts">
import { computed, toRefs } from 'vue'
import { getPlayableProp } from '@/utils/helpers'
import defaultCover from '@/../img/covers/default.svg'

const props = defineProps<{ playable: Playable }>()
const { playable } = toRefs(props)

const src = computed(() => getPlayableProp(playable.value, 'album_cover', 'episode_image'))
const author = computed(() => getPlayableProp(playable.value, 'artist_name', 'podcast_author'))
</script>
