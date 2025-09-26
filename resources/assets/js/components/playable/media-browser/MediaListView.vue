<template>
  <VirtualScroller
    v-slot="{ item: row }: { item: MediaRow }"
    tabindex="0"
    class="focus-visible:outline-none"
    :item-height="40"
    :items="rows"
    @scrolled-to-end="$emit('scrolled-to-end')"
    @keydown.enter.prevent.stop="handleEnter"
    @keydown.a.prevent="selectAllWithKeyboard"
  >
    <MediaListItem
      :key="row.item.id"
      :class="{ selected: row.selected }"
      :item="row.item"
      draggable="true"
      @click="onClick(row, $event)"
      @dragstart="onDragStart(row, $event)"
      @dblclick.prevent.stop="onDblclick(row)"
      @contextmenu.prevent="onContextMenu(row, $event)"
      @play-song="playSong(row.item as Song)"
      @open-folder="openFolder(row.item as Folder)"
    />
  </VirtualScroller>
</template>

<script setup lang="ts">
import isMobile from 'ismobilejs'
import { unionBy } from 'lodash'
import { computed, nextTick, reactive, toRefs, watch } from 'vue'
import { defineAsyncComponent } from '@/utils/helpers'
import { useRouter } from '@/composables/useRouter'
import { useDraggable } from '@/composables/useDragAndDrop'
import { useListSelection } from '@/composables/useListSelection'
import { isSong } from '@/utils/typeGuards'
import { queueStore } from '@/stores/queueStore'
import { mediaBrowser } from '@/services/mediaBrowser'
import { preferenceStore } from '@/stores/preferenceStore'
import { playableStore } from '@/stores/playableStore'
import { playback } from '@/services/playbackManager'
import { useContextMenu } from '@/composables/useContextMenu'

import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import MediaListItem from '@/components/playable/media-browser/MediaListItem.vue'

const props = defineProps<{ items: (Folder | Song)[], path: string }>()
defineEmits<{
  (e: 'press:enter', event: KeyboardEvent): void
  (e: 'scrolled-to-end'): void
}>()
const MediaBrowserContextMenu = defineAsyncComponent(() => import('@/components/media-browser/MediaBrowserContextMenu.vue'))
const PlayableContextMenu = defineAsyncComponent(() => import('@/components/playable/PlayableContextMenu.vue'))

const { items, path } = toRefs(props)

const { go, url } = useRouter()
const { startDragging } = useDraggable('browser-media')
const { openContextMenu } = useContextMenu()

const rows = computed(() => {
  return items.value.map((item): MediaRow => {
    return reactive({
      item,
      selected: false,
    })
  })
})

const {
  select,
  selectAllWithKeyboard,
  selectBetween,
  clearSelection,
  toggleSelected,
  isSelected,
  selected,
  lastSelected,
  reapplySelection,
} = useListSelection(rows, (row: MediaRow) => isSong(row.item) ? 'item.id' : 'item.path')

watch(items, () => reapplySelection(), { deep: true })

const selectedItems = computed(() => selected.value.map(row => row.item))
const onlySongsSelected = computed(() => selectedItems.value.every(isSong))
const singleFolderSelected = computed(() => selectedItems.value.length === 1 && !isSong(selectedItems.value[0]))

const handleEnter = async (event: KeyboardEvent) => {
  // if it's a simple Enter key press on a folder, open it
  if (singleFolderSelected.value && !event.shiftKey && !event.ctrlKey && !event.metaKey) {
    const folder = selectedItems.value[0] as Folder
    go(url('media-browser', { path: folder.path }))

    return
  }

  const resolvedSongs = onlySongsSelected.value
    ? selectedItems.value as Song[]
    : await playableStore.resolveSongsFromMediaReferences(mediaBrowser.extractMediaReferences(selectedItems.value))

  //  • Only Enter: Queue to bottom
  //  • Shift+Enter: Queues to top
  //  • Cmd/Ctrl+Enter: Queues to bottom and play the first selected item
  //  • Cmd/Ctrl+Shift+Enter: Queue to top and play the first queued item
  event.shiftKey ? queueStore.queueToTop(resolvedSongs) : queueStore.queue(resolvedSongs)

  if (event.ctrlKey || event.metaKey) {
    await playback().play(resolvedSongs[0])
  }

  go('queue')
}

const onDragStart = async (row: MediaRow, event: DragEvent) => {
  // If the user is dragging an unselected row, clear the current selection.
  if (!isSelected(row)) {
    clearSelection()
    select(row)
    await nextTick()
  }

  startDragging(event, selectedItems.value)
}

const onClick = (row: MediaRow, event: MouseEvent) => {
  // If we're on a touch device, or if Ctrl/Cmd key is pressed, just toggle selection.
  if (isMobile.any) {
    toggleSelected(row)
    return
  }

  if (event.ctrlKey || event.metaKey) {
    toggleSelected(row)
  }

  if (event.button === 0) {
    if (!(event.ctrlKey || event.metaKey || event.shiftKey)) {
      clearSelection()
      toggleSelected(row)
    }

    if (event.shiftKey && lastSelected.value) {
      selectBetween(lastSelected.value, row)
    }
  }
}

const playSong = async (song: Song) => {
  if (preferenceStore.state.continuous_playback) {
    const songsUnderPath = await playableStore.fetchSongsInFolder(path.value)
    // make sure the clicked playable is in the list
    queueStore.replaceQueueWith(unionBy(songsUnderPath, [song], 'id'))
    playback().play(song)
  } else {
    playback().queueAndPlay(song)
  }
}
const openFolder = (folder: Folder) => go(url('media-browser', { path: folder.path }))

const onDblclick = async (row: MediaRow) => {
  if (isSong(row.item)) {
    // If the user double-clicks a playable, play it, but with consideration to the continuous playback preference.
    playSong(row.item)
  } else {
    openFolder(row.item)
  }
}

const onContextMenu = async (row: MediaRow, event: MouseEvent) => {
  if (!row.selected) {
    clearSelection()
    toggleSelected(row)

    // awaiting a tick so that the selected items are collected properly
    await nextTick()
  }

  if (onlySongsSelected.value) {
    openContextMenu<'PLAYABLES'>(PlayableContextMenu, event, {
      playables: selectedItems.value as Song[],
    })
  } else {
    openContextMenu<'MEDIA_BROWSER'>(MediaBrowserContextMenu, event, {
      items: selectedItems.value,
    })
  }
}
</script>
