<template>
  <VirtualScroller
    v-slot="{ item: row }: { item: MediaRow }"
    tabindex="0"
    class="focus-visible:outline-none"
    :item-height="40"
    :items="rows"
    @scrolled-to-end="$emit('scrolledToEnd')"
    @keydown.enter.prevent.stop="handleEnter"
    @keydown.a.prevent="handleA"
  >
    <MediaBrowserItem
      :key="row.item.id"
      :class="{ selected: row.selected }"
      :item="row.item"
      draggable="true"
      @click="onClick(row, $event)"
      @dragstart="onDragStart(row, $event)"
      @dblclick.prevent.stop="onDblclick(row)"
      @contextmenu.prevent="onContextMenu(row, $event)"
    />
  </VirtualScroller>
</template>

<script setup lang="ts">
import { computed, nextTick, reactive, ref, toRefs } from 'vue'
import { findIndex } from 'lodash'
import { eventBus } from '@/utils/eventBus'
import { useRouter } from '@/composables/useRouter'
import { playbackService } from '@/services/playbackService'
import { useDraggable } from '@/composables/useDragAndDrop'
import { isSong } from '@/utils/typeGuards'

import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import MediaBrowserItem from '@/components/song/media-browser/MediaBrowserItem.vue'
import isMobile from 'ismobilejs'

const props = defineProps<{ items: (Folder | Song)[] }>()

defineEmits<{
  (e: 'press:enter', event: KeyboardEvent): void
  (e: 'scrolledToEnd'): void
}>()

const { items } = toRefs(props)

const { go, url } = useRouter()
const { startDragging } = useDraggable('browser-media')

const lastSelectedRow = ref<MediaRow | null>(null)

const rows = computed(() => {
  return items.value.map((item): MediaRow => {
    return reactive({
      item,
      selected: false,
    })
  })
})

const handleEnter = () => {
}

const clearSelection = () => rows.value.forEach(row => (row.selected = false))
const selectAllRows = () => rows.value.forEach(row => (row.selected = true))
const handleA = (event: KeyboardEvent) => (event.ctrlKey || event.metaKey) && selectAllRows()

const selectedItems = computed(() => rows.value.filter(row => row.selected).map(row => row.item))
const onlySongsSelected = computed(() => selectedItems.value.every(isSong))

const onDragStart = async (row: MediaRow, event: DragEvent) => {
  // If the user is dragging an unselected row, clear the current selection.
  if (!row.selected) {
    clearSelection()
    row.selected = true
    await nextTick()
  }

  startDragging(event, selectedItems.value)
}

const toggleRow = (row: MediaRow) => {
  row.selected = !row.selected
  lastSelectedRow.value = row
}

const selectRowsBetween = (first: MediaRow, second: MediaRow) => {
  const firstIndex = Math.max(0, findIndex(rows.value, row => row.item.id === first.item.id))
  const secondIndex = Math.max(0, findIndex(rows.value, row => row.item.id === second.item.id))
  const indexes = [firstIndex, secondIndex]
  indexes.sort((a, b) => a - b)

  for (let i = indexes[0]; i <= indexes[1]; ++i) {
    rows.value[i].selected = true
  }
}

const onClick = (row: MediaRow, event: MouseEvent) => {
  // If we're on a touch device, or if Ctrl/Cmd key is pressed, just toggle selection.
  if (isMobile.any) {
    toggleRow(row)
    return
  }

  if (event.ctrlKey || event.metaKey) {
    toggleRow(row)
  }

  if (event.button === 0) {
    if (!(event.ctrlKey || event.metaKey || event.shiftKey)) {
      clearSelection()
      toggleRow(row)
    }

    if (event.shiftKey && lastSelectedRow.value) {
      selectRowsBetween(lastSelectedRow.value, row)
    }
  }
}

const onDblclick = (row: MediaRow) => {
  if (isSong(row.item)) {
    playbackService.queueAndPlay(row.item)
  } else {
    go(url('media-browser', { path: row.item.path }))
  }
}

const onContextMenu = async (row: MediaRow, event: MouseEvent) => {
  if (!row.selected) {
    clearSelection()
    toggleRow(row)

    // awaiting a next tick so that the selected items are collected properly
    await nextTick()
  }

  if (onlySongsSelected.value) {
    eventBus.emit('PLAYABLE_CONTEXT_MENU_REQUESTED', event, selectedItems.value as Song[])
    return
  }

  eventBus.emit('MEDIA_BROWSER_CONTEXT_MENU_REQUESTED', event, selectedItems.value)
}
</script>

<style scoped lang="postcss">

</style>
