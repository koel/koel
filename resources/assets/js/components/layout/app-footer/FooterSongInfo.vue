<template>
  <div
    :class="{ playing: song?.playback_state === 'Playing' }"
    :draggable="draggable"
    class="song-info px-6 py-0 flex items-center content-start w-[84px] md:w-[420px] gap-5"
    @dragstart="onDragStart"
  >
    <span class="album-thumb block h-[55%] md:h-3/4 aspect-square rounded-full bg-cover" />
    <div v-if="song" class="meta overflow-hidden hidden md:block">
      <h3 class="title text-ellipsis overflow-hidden whitespace-nowrap">{{ song.title }}</h3>
      <a
        :href="artistOrPodcastUri"
        class="artist text-ellipsis overflow-hidden whitespace-nowrap block text-[0.9rem] !text-k-text-secondary hover:!text-k-accent"
      >
        {{ artistOrPodcastName }}
      </a>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { defaultCover, getPlayableProp, isSong, requireInjection } from '@/utils'
import { CurrentPlayableKey } from '@/symbols'
import { useDraggable } from '@/composables'

const { startDragging } = useDraggable('playables')

const song = requireInjection(CurrentPlayableKey, ref())

const cover = computed(() => {
  if (!song.value) return defaultCover
  return getPlayableProp(song.value, 'album_cover', 'episode_image')
})

const artistOrPodcastUri = computed(() => {
  if (!song.value) return ''
  return isSong(song.value) ? `#/artist/${song.value?.artist_id}` : `#/podcasts/${song.value.podcast_id}`
})

const artistOrPodcastName = computed(() => {
  if (!song.value) return ''
  return getPlayableProp(song.value, 'artist_name', 'podcast_title')
})

const coverBackgroundImage = computed(() => `url(${cover.value ?? defaultCover})`)
const draggable = computed(() => Boolean(song.value))

const onDragStart = (event: DragEvent) => {
  if (song.value) {
    startDragging(event, [song.value])
  }
}
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
