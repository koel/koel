<template>
  <div
    :class="{ playing: song?.playback_state === 'Playing' }"
    :draggable="draggable"
    class="song-info px-6 py-0 flex items-center content-start w-[84px] md:w-80 gap-5"
    @dragstart="onDragStart"
  >
    <span class="album-thumb block h-[55%] md:h-3/4 aspect-square rounded-full bg-cover" />
    <div v-if="song" class="meta overflow-hidden hidden md:block">
      <h3 class="title text-ellipsis overflow-hidden whitespace-nowrap">{{ song.title }}</h3>
      <a
        :href="`/#/artist/${song.artist_id}`"
        class="artist text-ellipsis overflow-hidden whitespace-nowrap block text-[0.9rem] !text-k-text-secondary hover:!text-k-accent"
      >
        {{ song.artist_name }}
      </a>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { defaultCover, requireInjection } from '@/utils'
import { CurrentSongKey } from '@/symbols'
import { useDraggable } from '@/composables'

const { startDragging } = useDraggable('songs')

const song = requireInjection(CurrentSongKey, ref())

const cover = computed(() => song.value?.album_cover || defaultCover)
const coverBackgroundImage = computed(() => `url(${cover.value})`)
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
