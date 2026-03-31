<template>
  <li
    :class="{ playing }"
    class="group flex items-center gap-4 px-6 py-4 cursor-pointer hover:bg-k-fg-5"
    data-testid="song-card"
    draggable="true"
    tabindex="0"
    @dblclick="play"
    @keydown.enter.prevent="play"
    @dragstart="onDragStart"
    @contextmenu.prevent="onContextMenu"
  >
    <PlayableThumbnail :playable @clicked="play" />
    <span class="flex-1 min-w-0 gap-1 flex flex-col">
      <span class="title block truncate">{{ playable.title }}</span>
      <span class="block truncate text-k-fg-50 text-[0.9rem]">{{ artist }}</span>
    </span>
    <span class="text-k-fg-50 text-[0.9rem] tabular-nums">
      {{ fmtLength }}
    </span>
  </li>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { defineAsyncComponent, getPlayableProp } from '@/utils/helpers'
import { secondsToHis } from '@/utils/formatters'
import { useDraggable } from '@/composables/useDragAndDrop'
import { useContextMenu } from '@/composables/useContextMenu'
import { playback } from '@/services/playbackManager'

import PlayableThumbnail from '@/components/playable/PlayableThumbnail.vue'

const PlayableContextMenu = defineAsyncComponent(() => import('@/components/playable/PlayableContextMenu.vue'))

const props = defineProps<{ playable: Playable }>()
const { playable } = toRefs(props)

const { startDragging } = useDraggable('playables')
const { openContextMenu } = useContextMenu()

const artist = computed(() => getPlayableProp<string>(playable.value, 'artist_name', 'podcast_author') || '')
const playing = computed(() => ['Playing', 'Paused'].includes(playable.value.playback_state!))
const fmtLength = secondsToHis(playable.value.length)

const play = () => {
  if (playable.value.playback_state === 'Playing') {
    playback().pause()
  } else if (playable.value.playback_state === 'Paused') {
    playback().resume()
  } else {
    playback().play(playable.value)
  }
}

const onDragStart = (event: DragEvent) => startDragging(event, [playable.value])

const onContextMenu = (event: MouseEvent) => {
  openContextMenu<'PLAYABLES'>(PlayableContextMenu, event, { playables: [playable.value] })
}
</script>

<style lang="postcss" scoped>
li.playing .title {
  @apply text-k-highlight;
}
</style>
