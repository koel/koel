<template>
  <article
    :class="{ playing: song.playback_state === 'Playing' || song.playback_state === 'Paused' }"
    class="group flex gap-3 py-2 pl-2.5 pr-3 rounded-md items-center bg-k-bg-secondary border border-k-border
    hover:border-white/15 transition-[border-color] duration-200 ease-in-out
    focus:ring-1 focus:ring-k-accent focus-within:ring-1 focus-within:ring-k-accent"
    draggable="true"
    tabindex="0"
    @dragstart="onDragStart"
    @contextmenu.prevent="requestContextMenu"
    @dblclick.prevent="play"
  >
    <span>
      <SongThumbnail :song="song" />
    </span>
    <main class="flex-1 flex items-start">
      <div class="flex-1 space-y-1 overflow-hidden">
        <h3 class="flex gap-2 overflow-hidden text-ellipsis whitespace-nowrap">
          <ExternalMark v-if="external" />
          {{ song.title }}
        </h3>
        <p class="text-k-text-secondary text-[0.9rem] opacity-80">
          <a :href="`#/artist/${song.artist_id}`" class="!text-k-text-primary hover:!text-k-accent">
            {{ song.artist_name }}
          </a>
          - {{ pluralize(song.play_count, 'play') }}
        </p>
      </div>
      <LikeButton :song="song" class="opacity-0 text-k-text-secondary group-hover:opacity-100" />
    </main>
  </article>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { eventBus, pluralize } from '@/utils'
import { queueStore } from '@/stores'
import { playbackService } from '@/services'
import { useAuthorization, useDraggable, useKoelPlus } from '@/composables'

import SongThumbnail from '@/components/song/SongThumbnail.vue'
import LikeButton from '@/components/song/SongLikeButton.vue'
import ExternalMark from '@/components/ui/ExternalMark.vue'

const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)

const { isPlus } = useKoelPlus()
const { currentUser } = useAuthorization()
const { startDragging } = useDraggable('songs')

const external = computed(() => isPlus.value && song.value.owner_id !== currentUser.value?.id)

const requestContextMenu = (event: MouseEvent) => eventBus.emit('SONG_CONTEXT_MENU_REQUESTED', event, song.value)
const onDragStart = (event: DragEvent) => startDragging(event, [song.value])

const play = () => {
  queueStore.queueIfNotQueued(song.value)
  playbackService.play(song.value)
}
</script>

<style lang="postcss" scoped>
article {
  &.playing {
    @apply text-k-accent;
  }

  /* show the thumbnail's playback control on the whole card focus and hover */

  &:hover :deep(.song-thumbnail), &:focus :deep(.song-thumbnail) {
    &::before {
      @apply opacity-70;
    }
  }
}
</style>
