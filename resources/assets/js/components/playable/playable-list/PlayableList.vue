<template>
  <div
    ref="wrapper"
    class="playable-list-wrap relative flex flex-col flex-1 overflow-auto py-0"
    data-testid="song-list"
    @keydown.delete.prevent.stop="handleDelete"
    @keydown.enter.prevent.stop="handleEnter"
    @keydown.a.prevent="selectAllWithKeyboard"
  >
    <PlayableListHeader v-if="config.hasHeader" :content-type="contentType" @sort="sort" />

    <VirtualScroller
      ref="virtualScroller"
      v-slot="{ item }: { item: PlayableRow }"
      :item-height="calculatedItemHeight"
      :items="rows"
      @scrolled-to-end="$emit('scrolled-to-end')"
    >
      <PlayableListItem
        :key="item.playable.id"
        :item="item"
        :show-disc="showDiscLabel(item.playable)"
        :draggable="!isMobile.any"
        @click="onClick(item, $event)"
        @dragleave="onDragLeave"
        @dragstart="onDragStart(item, $event)"
        @play="onPlay(item.playable)"
        @contextmenu.prevent="onContextMenu(item, $event)"
        @dragover.prevent="onDragOver"
        @drop.prevent="onDrop(item, $event)"
        @dragend.prevent="onDragEnd"
      />
    </VirtualScroller>
  </div>
</template>

<script lang="ts" setup>
import { useThrottleFn } from '@vueuse/core'
import isMobile from 'ismobilejs'
import type { Ref } from 'vue'
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import { defineAsyncComponent, requireInjection } from '@/utils/helpers'
import { getPlayableCollectionContentType } from '@/utils/typeGuards'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { queueStore } from '@/stores/queueStore'
import { useDraggable, useDroppable } from '@/composables/useDragAndDrop'
import { useListSelection } from '@/composables/useListSelection'
import { playback } from '@/services/playbackManager'
import { useSwipeDirection } from '@/composables/useSwipeDirection'
import { useContextMenu } from '@/composables/useContextMenu'

import {
  FilteredPlayablesKey,
  PlayableListConfigKey,
  PlayableListContextKey,
  PlayableListSortFieldKey,
  SelectedPlayablesKey,
} from '@/config/symbols'

import PlayableListItem from '@/components/playable/playable-list/PlayableListItem.vue'
import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import PlayableListHeader from '@/components/playable/playable-list/PlayableListHeader.vue'

const emit = defineEmits<{
  (e: 'press:enter', event: KeyboardEvent): void
  (e: 'press:delete'): void
  (e: 'reorder', song: Playable, placement: Placement): void
  (e: 'sort', field: MaybeArray<PlayableListSortField>, order: SortOrder): void
  (e: 'swipe', direction: 'up' | 'down'): void
  (e: 'scrolled-to-end'): void
}>()

const PlayableContextMenu = defineAsyncComponent(() => import('@/components/playable/PlayableContextMenu.vue'))

const { startDragging } = useDraggable('playables')
const { getDroppedData, acceptsDrop } = useDroppable(['playables'])
const { openContextMenu } = useContextMenu()

const [playables] = requireInjection<[Ref<Playable[]>]>(FilteredPlayablesKey)
const [selectedPlayables, setSelectedPlayables] = requireInjection<[Ref<Playable[]>, Closure]>(SelectedPlayablesKey)
const [sortField] = requireInjection<[Ref<MaybeArray<PlayableListSortField>>, Closure]>(PlayableListSortFieldKey)
const [config] = requireInjection<[Partial<PlayableListConfig>]>(PlayableListConfigKey, [{}])
const [context] = requireInjection<[PlayableListContext]>(PlayableListContextKey)

const wrapper = ref<HTMLElement>()
const virtualScroller = ref<InstanceType<typeof VirtualScroller>>()
const sortFields = ref<PlayableListSortField[]>([])

useSwipeDirection(
  () => wrapper.value,
  direction => emit('swipe', direction),
)

const rows = computed(() => {
  return playables.value.map<PlayableRow>(playable => {
    return reactive({
      playable,
      selected: false,
    })
  })
})

const {
  select,
  selectAllWithKeyboard,
  clearSelection,
  toggleSelected,
  isSelected,
  selectBetween,
  inSelectedRange,
  lastSelected,
  selected,
  reapplySelection,
} = useListSelection(rows, 'playable.id')

const shouldTriggerContinuousPlayback = computed(() => {
  return (
    preferences.continuous_playback &&
    typeof context.type !== 'undefined' &&
    ['Playlist', 'Album', 'Artist', 'Genre', 'Favorites'].includes(context.type)
  )
})

const contentType = computed(() => getPlayableCollectionContentType(rows.value.map(({ playable }) => playable)))

const getAllPlayablesWithSort = () => rows.value.map(row => row.playable)

watch(selected, () => setSelectedPlayables(selected.value.map(({ playable }) => playable)), { deep: true })

const sort = (field: MaybeArray<PlayableListSortField>, order: SortOrder) => {
  // we simply pass the sort event from the header up to the parent component
  emit('sort', field, order)
}

const render = () => {
  config.sortable || (sortFields.value = [])
  reapplySelection()
}

watch(playables, () => render(), { deep: true })

const handleDelete = () => {
  emit('press:delete') // eslint-disable-line vue/custom-event-name-casing
  clearSelection()
}

const handleEnter = (event: KeyboardEvent) => {
  emit('press:enter', event) // eslint-disable-line vue/custom-event-name-casing
  clearSelection()
}

const onDragStart = async (row: PlayableRow, event: DragEvent) => {
  // If the user is dragging an unselected row, clear the current selection.
  if (!isSelected(row)) {
    clearSelection()
    select(row)
    await nextTick()
  }

  // Add "dragging" class to the wrapper so that we can disable pointer events on child elements.
  // This prevents dragleave events from firing when the user drags the mouse over the child elements.
  wrapper.value?.classList.add('dragging')

  startDragging(event, selectedPlayables.value)
}

let currentDropTarget: HTMLElement | null = null

const clearDropTarget = () => {
  currentDropTarget?.classList.remove('droppable', 'dragover-top', 'dragover-bottom')
  currentDropTarget = null
}

const onDragOver = useThrottleFn((event: DragEvent) => {
  if (!config.reorderable) {
    return
  }

  if (acceptsDrop(event)) {
    const target = (event.target as HTMLElement).closest('.playable-list-item') as HTMLElement | null

    if (!target) {
      return
    }

    // If we moved to a different item, clear the old one
    if (currentDropTarget && currentDropTarget !== target) {
      clearDropTarget()
    }

    currentDropTarget = target

    const rect = target.getBoundingClientRect()
    const midPoint = rect.top + rect.height / 2
    target.classList.remove('dragover-top', 'dragover-bottom')
    target.classList.add('droppable', event.clientY < midPoint ? 'dragover-top' : 'dragover-bottom')
  }

  return false
}, 50)

const onDragLeave = (event: DragEvent) => {
  // Only clear if the cursor actually left the item (not just moved between children)
  const related = event.relatedTarget

  if (!(related instanceof Node) || !currentDropTarget?.contains(related)) {
    clearDropTarget()
  }

  return false
}

const onDrop = (row: PlayableRow, event: DragEvent) => {
  if (!config.reorderable || !getDroppedData(event) || !selectedPlayables.value.length) {
    wrapper.value?.classList.remove('dragging')
    clearDropTarget()
    return false
  }

  wrapper.value?.classList.remove('dragging')

  if (!inSelectedRange(row)) {
    emit('reorder', row.playable, currentDropTarget?.classList.contains('dragover-bottom') ? 'after' : 'before')
  }

  clearDropTarget()
  return false
}

const onDragEnd = () => {
  wrapper.value?.classList.remove('dragging')
  clearDropTarget()
}

const onClick = (row: PlayableRow, event: MouseEvent) => {
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

const onContextMenu = async (row: PlayableRow, event: MouseEvent) => {
  if (!isSelected(row)) {
    clearSelection()
    toggleSelected(row)

    // await a tick so that the selected items are collected properly
    await nextTick()
  }

  openContextMenu<'PLAYABLES'>(PlayableContextMenu, event, {
    playables: selectedPlayables.value,
  })
}

const onPlay = async (playable: Playable) => {
  if (playable.playback_state === 'Stopped') {
    if (shouldTriggerContinuousPlayback.value) {
      queueStore.replaceQueueWith(getAllPlayablesWithSort())
    }

    await playback().play(playable)
  } else if (playable.playback_state === 'Paused') {
    await playback().resume()
  } else {
    await playback().pause()
  }
}

const discIndexMap = computed(() => {
  const map: { [key: number]: number } = {}

  rows.value.forEach((row, index) => {
    const { disc } = row.playable as Song
    if (!Object.values(map).includes(disc)) {
      map[index] = disc
    }
  })

  return map
})

const noOrOneDiscOnly = computed(() => Object.keys(discIndexMap.value).length <= 1)
const sortingByTrack = computed(() => sortField.value === 'track')
const inAlbumContext = computed(() => context.type === 'Album')

const noDiscLabel = computed(() => noOrOneDiscOnly.value || !sortingByTrack.value || !inAlbumContext.value)

const showDiscLabel = (row: Playable) => {
  if (noDiscLabel.value) {
    return false
  }

  const index = rows.value.findIndex(({ playable }) => playable.id === row.id)
  return discIndexMap.value[index] !== undefined
}

const standardSongItemHeight = 64
const discNumberHeight = 32.5

const calculatedItemHeight = computed(() => {
  if (noDiscLabel.value) {
    return standardSongItemHeight
  }

  const discCount = Object.keys(discIndexMap.value).length
  const totalAdditionalPixels = discCount * discNumberHeight

  const totalHeight = rows.value.length * standardSongItemHeight + totalAdditionalPixels

  return totalHeight / rows.value.length
})

const scrollToPlayable = (playable: Playable) => {
  const index = rows.value.findIndex(row => row.playable.id === playable.id)

  if (index >= 0) {
    virtualScroller.value?.scrollToIndex(index)
  }
}

defineExpose({
  getAllPlayablesWithSort,
  scrollToPlayable,
})

onMounted(() => render())
</script>

<style lang="postcss">
@reference '@css/app.pcss';
.playable-list-wrap {
  .virtual-scroller {
    @apply flex-1;
  }

  &.dragging .song-item * {
    @apply pointer-events-none;
  }

  .song-list-header > span,
  .song-item > span {
    @apply text-left p-2 align-middle truncate;

    &.time {
      @apply basis-20 overflow-visible;
    }

    &.track-number {
      @apply basis-16;
    }

    &.album {
      @apply basis-[27%];
    }

    &.collaborator {
      @apply basis-20;
    }

    &.year {
      @apply basis-[64px] text-left;
    }

    &.genre {
      @apply basis-48 text-left;
    }

    &.added-at {
      @apply basis-44 text-left;
    }

    &.extra {
      @apply basis-12 text-center;
    }

    &.play {
      @apply hidden no-hover:block;
    }

    &.title-artist {
      @apply flex-1;
    }
  }

  .song-list-header {
    @apply tracking-widest uppercase cursor-pointer text-k-fg-70;

    .extra {
      @apply px-0;
    }
  }

  .unsortable span {
    @apply cursor-default;
  }

  @media only screen and (max-width: 768px) {
    .scroller {
      top: 0;

      .item-container {
        left: 12px;
        right: 12px;
        width: calc(200vw - 24px);
      }
    }

    .song-item {
      padding: 8px 12px;
      position: relative;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      width: 200%;
    }

    .song-item :is(.track-number, .album, .time, .year, .genre, .collaborator, .added-at),
    .song-list-header :is(.track-number, .album, .time, .year, .genre, .collaborator, .added-at) {
      display: none;
    }

    .song-item span {
      padding: 0;
      vertical-align: bottom;

      &.thumbnail {
        display: block;
        padding-right: 12px;
      }
    }
  }
}
</style>
