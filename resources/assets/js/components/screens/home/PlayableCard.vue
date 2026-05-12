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
      <span class="title flex gap-2 items-center truncate">
        <Icon
          v-if="cachingOffline"
          :icon="faSpinner"
          class="opacity-50!"
          spin
          title="Caching for offline playback"
          aria-label="Caching for offline playback"
        />
        <Icon
          v-else-if="cachingFailed"
          :icon="faExclamationTriangle"
          class="text-k-danger opacity-75!"
          :title="`Error: ${cachingErrorMessage}`"
        />
        <OfflineMark v-else-if="cachedOffline" />
        <span class="truncate">{{ playable.title }}</span>
      </span>
      <span class="block truncate text-k-fg-50 text-[0.9rem]">{{ artist }}</span>
    </span>
    <span class="text-k-fg-50 text-[0.9rem] tabular-nums">
      {{ fmtLength }}
    </span>
  </li>
</template>

<script lang="ts" setup>
import { faExclamationTriangle, faSpinner } from '@fortawesome/free-solid-svg-icons'
import { computed, toRefs } from 'vue'
import { defineAsyncComponent, getPlayableProp } from '@/utils/helpers'
import { secondsToHis } from '@/utils/formatters'
import { isSong } from '@/utils/typeGuards'
import { useDraggable } from '@/composables/useDragAndDrop'
import { useContextMenu } from '@/composables/useContextMenu'
import { useOfflinePlayback } from '@/composables/useOfflinePlayback'
import { playback } from '@/services/playbackManager'

import PlayableThumbnail from '@/components/playable/PlayableThumbnail.vue'
import OfflineMark from '@/components/ui/OfflineMark.vue'

const PlayableContextMenu = defineAsyncComponent(() => import('@/components/playable/PlayableContextMenu.vue'))

const props = defineProps<{ playable: Playable }>()
const { playable } = toRefs(props)

const { startDragging } = useDraggable('playables')
const { openContextMenu } = useContextMenu()
const { isCached, isCaching, hasCachingError, getCachingError } = useOfflinePlayback()

const artist = computed(() => getPlayableProp<string>(playable.value, 'artist_name', 'podcast_author') || '')
const playing = computed(() => ['Playing', 'Paused'].includes(playable.value.playback_state!))
const cachedOffline = computed(() => isSong(playable.value) && isCached(playable.value))
const cachingOffline = computed(() => isSong(playable.value) && isCaching(playable.value))
const cachingFailed = computed(() => isSong(playable.value) && hasCachingError(playable.value))
const cachingErrorMessage = computed(() => getCachingError(playable.value))
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
@reference '@css/app.pcss';
li.playing .title {
  @apply text-k-highlight;
}
</style>
