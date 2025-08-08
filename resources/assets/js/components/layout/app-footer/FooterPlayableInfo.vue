<template>
  <div
    :class="{ playing: playable?.playback_state === 'Playing' }"
    :draggable="draggable"
    class="song-info px-6 py-0 flex items-center content-start w-[84px] md:w-[420px] gap-5"
    @dragstart="onDragStart"
  >
    <span class="album-thumb block h-[55%] md:h-3/4 aspect-square rounded-full bg-cover" />
    <div v-if="playable" class="meta overflow-hidden hidden md:block">
      <h3 class="title text-ellipsis overflow-hidden whitespace-nowrap">{{ playable.title }}</h3>
      <a
        :href="artistOrPodcastUri"
        class="artist text-ellipsis overflow-hidden whitespace-nowrap block text-[0.9rem] text-k-text-secondary"
      >
        {{ artistOrPodcastName }}
      </a>
    </div>
  </div>
</template>

<script lang="ts" setup>
import type { Ref } from 'vue'
import { computed, ref } from 'vue'
import defaultCover from '@/../img/covers/default.svg'
import { getPlayableProp, requireInjection, use } from '@/utils/helpers'
import { isSong } from '@/utils/typeGuards'
import { CurrentStreamableKey } from '@/symbols'
import { useDraggable } from '@/composables/useDragAndDrop'
import { useRouter } from '@/composables/useRouter'

const { startDragging } = useDraggable('playables')
const { url } = useRouter()

const playable = requireInjection<Ref<Playable | undefined>>(CurrentStreamableKey, ref())

const cover = computed(() => playable.value
  ? getPlayableProp(playable.value, 'album_cover', 'episode_image')
  : defaultCover,
)

const artistOrPodcastUri = computed(() => {
  if (!playable.value) {
    return ''
  }

  return isSong(playable.value)
    ? url('artists.show', { id: playable.value?.artist_id })
    : url('podcasts.show', { id: playable.value?.podcast_id })
})

const artistOrPodcastName = computed(() => playable.value
  ? getPlayableProp(playable.value, 'artist_name', 'podcast_title')
  : '',
)

const coverBackgroundImage = computed(() => `url(${cover.value ?? defaultCover})`)
const draggable = computed(() => Boolean(playable.value))

const onDragStart = (event: DragEvent) => use(playable.value, p => startDragging(event, [p]))
</script>

<style lang="postcss" scoped>
.song-info {
  :fullscreen & {
    @apply pl-0;
  }

  .album-thumb {
    background-image: v-bind(coverBackgroundImage);

    :fullscreen & {
      @apply h-20;
    }
  }

  .meta {
    :fullscreen & {
      @apply -mt-72 origin-bottom-left absolute overflow-hidden;

      .title {
        @apply text-5xl mb-[0.4rem] font-bold;
      }

      .artist {
        @apply text-3xl w-fit;
      }
    }
  }

  &.playing .album-thumb {
    @apply motion-reduce:animate-none;
    animation: spin 30s linear infinite;
  }
}

@keyframes spin {
  100% {
    transform: rotate(360deg);
  }
}
</style>
