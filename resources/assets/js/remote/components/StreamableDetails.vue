<template>
  <article class="flex-1 flex flex-col items-center justify-around w-screen">
    <img
      :src="coverArt"
      class="my-0 mx-auto w-[calc(70vw_+_4px)] aspect-square rounded-full border-2 border-solid border-k-text-primary object-center object-cover"
      alt="Cover art"
    >
    <div class="w-full flex flex-col justify-around px-6">
      <div>
        <p class="text-[6vmin] font-bold mx-auto mb-4">{{ title }}</p>
        <div v-if="description" class="text-[4vmin] leading-7 line-clamp-4 opacity-50">{{ description }}</div>
        <p class="text-[5vmin] mb-2 opacity-50">{{ artist }}</p>
        <p class="text-[4vmin] opacity-50">{{ album }}</p>
      </div>
    </div>
  </article>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import defaultCover from '@/../img/covers/default.svg'
import { getPlayableProp } from '@/utils/helpers'
import { isRadioStation } from '@/utils/typeGuards'

const props = defineProps<{ streamable: Streamable }>()
const { streamable } = toRefs(props)

const coverArt = computed(() => {
  const src = isRadioStation(streamable.value)
    ? streamable.value.logo
    : getPlayableProp(streamable.value, 'album_cover', 'episode_image')

  return src || defaultCover
})

const title = computed(() => isRadioStation(streamable.value) ? streamable.value.name : streamable.value.title)
const description = computed(() => isRadioStation(streamable.value) ? streamable.value.description : null)

const artist = computed(() => isRadioStation(streamable.value)
  ? ''
  : getPlayableProp(streamable.value, 'artist_name', 'podcast_author'),
)

const album = computed(() => isRadioStation(streamable.value)
  ? ''
  : getPlayableProp(streamable.value, 'album_name', 'podcast_title'),
)
</script>

<style lang="postcss" scoped>
p {
  @apply max-w-[90%] mx-auto overflow-hidden text-ellipsis whitespace-nowrap leading-[1.3];
}
</style>
