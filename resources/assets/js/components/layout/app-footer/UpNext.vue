<template>
  <article
    v-if="playable"
    class="fixed z-[99] right-[5vw] top-[4.5rem] flex bg-k-bg border border-px border-k-fg-10"
  >
    <span :style="{ backgroundImage: `url(${defaultCover})` }">
      <img :src alt="Cover image" class="w-[96px] aspect-square object-cover" loading="lazy">
    </span>
    <main class="px-5 py-4 min-w-80 max-w-96 flex flex-col justify-between overflow-hidden">
      <h4 class="uppercase">Up Next</h4>
      <p class="text-k-fg text-xl overflow-hidden whitespace-nowrap overflow-ellipsis">
        {{ playable.title }}
      </p>
      <p class="overflow-hidden whitespace-nowrap overflow-ellipsis">{{ author }}</p>
    </main>
  </article>
</template>

<script setup lang="ts">
import { computed, toRefs } from 'vue'
import { useI18n } from 'vue-i18n'
import { getPlayableProp } from '@/utils/helpers'
import { isSong } from '@/utils/typeGuards'
import { useBranding } from '@/composables/useBranding'
import { artistStore } from '@/stores/artistStore'

const props = defineProps<{ playable: Playable }>()
const { playable } = toRefs(props)

const { t } = useI18n()
const { cover: defaultCover } = useBranding()

const src = computed(() => getPlayableProp(playable.value, 'album_cover', 'episode_image'))
const author = computed(() => {
  const artistName = getPlayableProp(playable.value, 'artist_name', 'podcast_author')
  if (isSong(playable.value) && artistStore.isUnknown(artistName)) {
    return t('screens.unknownArtist')
  }
  return artistName
})
</script>
