<template>
  <div
    ref="wrapper"
    class="song-list-wrap relative flex flex-col flex-1 overflow-auto py-0 px-3 md:p-0"
    data-testid="song-list"
    tabindex="0"
    @keydown.delete.prevent.stop="handleDelete"
    @keydown.enter.prevent.stop="handleEnter"
    @keydown.a.prevent="handleA"
  >
    <div
      :class="config.sortable ? 'sortable' : 'unsortable'"
      class="song-list-header flex z-[2] bg-k-bg-secondary"
    >
      <span
        class="track-number"
        data-testid="header-track-number"
        role="button"
        title="Sort by track number"
        @click="sort('track')"
      >
        #
        <template v-if="config.sortable">
          <Icon v-if="sortField === 'track' && sortOrder === 'asc'" :icon="faCaretDown" class="text-k-highlight" />
          <Icon v-if="sortField === 'track' && sortOrder === 'desc'" :icon="faCaretUp" class="text-k-highlight" />
        </template>
      </span>
      <span
        class="title-artist"
        data-testid="header-title"
        role="button"
        title="Sort by title"
        @click="sort('title')"
      >
        Title
        <template v-if="config.sortable">
          <Icon v-if="sortField === 'title' && sortOrder === 'asc'" :icon="faCaretDown" class="text-k-highlight" />
          <Icon v-if="sortField === 'title' && sortOrder === 'desc'" :icon="faCaretUp" class="text-k-highlight" />
        </template>
      </span>
      <span
        class="album"
        data-testid="header-album"
        role="button"
        :title="`Sort by ${contentType === 'episodes' ? 'podcast' : (contentType === 'songs' ? 'album' : 'album/podcast')}`"
        @click="sort(contentType === 'episodes' ? 'podcast_title' : (contentType === 'songs' ? 'album_name' : ['album_name', 'podcast_title']))"
      >
        <template v-if="contentType === 'episodes'">Podcast</template>
        <template v-else-if="contentType === 'songs'">Album</template>
        <template v-else>Album <span class="opacity-50">/</span> Podcast</template>

        <span v-if="config.sortable" class="ml-2">
          <Icon v-if="sortingByAlbumOrPodcast && sortOrder === 'asc'" :icon="faCaretDown" class="text-k-highlight" />
          <Icon v-if="sortingByAlbumOrPodcast && sortOrder === 'desc'" :icon="faCaretUp" class="text-k-highlight" />
        </span>
      </span>
      <template v-if="config.collaborative">
        <span class="collaborator">User</span>
        <span class="added-at">Added</span>
      </template>
      <span
        class="time"
        data-testid="header-length"
        role="button"
        title="Sort by duration"
        @click="sort('length')"
      >
        Time
        <template v-if="config.sortable">
          <Icon v-if="sortField === 'length' && sortOrder === 'asc'" :icon="faCaretDown" class="text-k-highlight" />
          <Icon v-if="sortField === 'length' && sortOrder === 'desc'" :icon="faCaretUp" class="text-k-highlight" />
        </template>
      </span>
      <span class="extra">
        <SongListSorter
          v-if="config.sortable"
          :field="sortField"
          :has-custom-order-sort="config.hasCustomOrderSort"
          :order="sortOrder"
          :content-type="contentType"
          @sort="sort"
        />
      </span>
    </div>

    <VirtualScroller
      v-slot="{ item }: { item: PlayableRow }"
      :item-height="64"
      :items="filteredRows"
      @scroll="onScroll"
      @scrolled-to-end="$emit('scrolled-to-end')"
    >
      <SongListItem
        :key="item.playable.id"
        :item="item"
        draggable="true"
        @click="onClick(item, $event)"
        @dragleave="onDragLeave"
        @dragstart="onDragStart(item, $event)"
        @play="onPlay(item.playable)"
        @dragover.prevent="onDragOver"
        @drop.prevent="onDrop(item, $event)"
        @dragend.prevent="onDragEnd"
        @contextmenu.prevent="openContextMenu(item, $event)"
      />
    </VirtualScroller>
  </div>
</template>

<script lang="ts" setup>
import Fuse from 'fuse.js'
import { findIndex, findLastIndex, throttle } from 'lodash'
import isMobile from 'ismobilejs'
import { faCaretDown, faCaretUp } from '@fortawesome/free-solid-svg-icons'
import { computed, nextTick, onMounted, Ref, ref, watch } from 'vue'
import { arrayify, eventBus, getPlayableCollectionContentType, requireInjection } from '@/utils'
import { preferenceStore as preferences, queueStore } from '@/stores'
import { useDraggable, useDroppable } from '@/composables'
import { playbackService } from '@/services'
import {
  PlayableListConfigKey,
  PlayableListContextKey,
  PlayableListSortFieldKey,
  PlayablesKey,
  SelectedPlayablesKey,
  SongListFilterKeywordsKey,
  SongListSortOrderKey
} from '@/symbols'

import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import SongListItem from '@/components/song/SongListItem.vue'
import SongListSorter from '@/components/song/SongListSorter.vue'

const { startDragging } = useDraggable('playables')
const { getDroppedData, acceptsDrop } = useDroppable(['playables'])

const emit = defineEmits<{
  (e: 'press:enter', event: KeyboardEvent): void,
  (e: 'press:delete'): void,
  (e: 'reorder', song: Playable, type: MoveType): void,
  (e: 'sort', field: MaybeArray<PlayableListSortField>, order: SortOrder): void,
  (e: 'scroll-breakpoint', direction: 'up' | 'down'): void,
  (e: 'scrolled-to-end'): void,
}>()

const [items] = requireInjection<[Ref<Playable[]>]>(PlayablesKey)
const [selectedPlayables, setSelectedPlayables] = requireInjection<[Ref<Playable[]>, Closure]>(SelectedPlayablesKey)
const [sortField, setSortField] = requireInjection<[Ref<MaybeArray<PlayableListSortField>>, Closure]>(PlayableListSortFieldKey)
const [sortOrder, setSortOrder] = requireInjection<[Ref<SortOrder>, Closure]>(SongListSortOrderKey)
const [config] = requireInjection<[Partial<PlayableListConfig>]>(PlayableListConfigKey, [{}])
const [context] = requireInjection<[PlayableListContext]>(PlayableListContextKey)

const filterKeywords = requireInjection(SongListFilterKeywordsKey, ref(''))
let fuse: Fuse<PlayableRow> | null = null

const wrapper = ref<HTMLElement>()
const lastSelectedRow = ref<PlayableRow>()
const sortFields = ref<PlayableListSortField[]>([])
const rows = ref<PlayableRow[]>([])

const shouldTriggerContinuousPlayback = computed(() => {
  return preferences.continuous_playback
    && typeof context.type !== 'undefined'
    && ['Playlist', 'Album', 'Artist', 'Genre', 'Favorites'].includes(context.type)
})

const contentType = computed(() => getPlayableCollectionContentType(rows.value.map(({ playable }) => playable)))

const sortingByAlbumOrPodcast = computed(() => {
  const sortFields = arrayify(sortField.value)
  return sortFields[0] === 'album_name' || sortFields[0] === 'podcast_title'
})

watch(
  rows,
  () => setSelectedPlayables(rows.value.filter(({ selected }) => selected).map(({ playable }) => playable)),
  { deep: true }
)

watch(rows, () => {
  fuse = new Fuse(rows.value, {
    keys: [
      'playable.title',
      'playable.artist_name',
      'playable.album_name',
      'playable.podcast_title',
      'playable.podcast_author',
      'playable.episode_description'
    ]
  })
}, { immediate: true })

const filteredRows = computed<PlayableRow[]>(() => {
  const keywords = filterKeywords.value.trim()

  if (!keywords) {
    return rows.value
  }

  return fuse?.search(keywords).map(result => result.item) || []
})

let lastScrollTop = 0

const onScroll = (e: Event) => {
  const scroller = e.target as HTMLElement

  if (scroller.scrollTop > 512 && lastScrollTop < 512) {
    emit('scroll-breakpoint', 'down')
  } else if (scroller.scrollTop < 512 && lastScrollTop > 512) {
    emit('scroll-breakpoint', 'up')
  }

  lastScrollTop = scroller.scrollTop
}

/**
 * Since playable objects themselves are shared by all lists, we can't use them directly to
 * determine their selection status (selected/unselected). Therefore, for each list, we
 * maintain an array of "playable rows," each containing the playable itself and the "selected" flag.
 */
const generateRows = () => {
  // Since this method re-generates the playable wrappers, we need to keep track of  the
  // selected playable manually.
  const selectedIds = selectedPlayables.value.map(playable => playable.id)

  return items.value.map<PlayableRow>(playable => ({
    playable,
    selected: selectedIds.includes(playable.id)
  }))
}

const sort = (field: MaybeArray<PlayableListSortField>) => {
  // there are certain circumstances where sorting is simply disallowed, e.g. in Queue
  if (!config.sortable) {
    return
  }

  setSortField(field)
  setSortOrder(sortOrder.value === 'asc' ? 'desc' : 'asc')

  emit('sort', field, sortOrder.value)
}

const render = () => {
  config.sortable || (sortFields.value = [])
  rows.value = generateRows()
}

watch(items, () => render(), { deep: true })

const handleDelete = () => {
  emit('press:delete')
  clearSelection()
}

const handleEnter = (event: KeyboardEvent) => {
  emit('press:enter', event)
  clearSelection()
}

/**
 * Select all (filtered) rows in the current list.
 */
const selectAllRows = () => rows.value.forEach(row => (row.selected = true))
const clearSelection = () => rows.value.forEach(row => (row.selected = false))
const handleA = (event: KeyboardEvent) => (event.ctrlKey || event.metaKey) && selectAllRows()
const getAllPlayablesWithSort = () => rows.value.map(row => row.playable)

const onClick = (row: PlayableRow, event: MouseEvent) => {
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

const toggleRow = (row: PlayableRow) => {
  row.selected = !row.selected
  lastSelectedRow.value = row
}

const selectRowsBetween = (first: PlayableRow, second: PlayableRow) => {
  const firstIndex = Math.max(0, findIndex(rows.value, row => row.playable.id === first.playable.id))
  const secondIndex = Math.max(0, findIndex(rows.value, row => row.playable.id === second.playable.id))
  const indexes = [firstIndex, secondIndex]
  indexes.sort((a, b) => a - b)

  for (let i = indexes[0]; i <= indexes[1]; ++i) {
    rows.value[i].selected = true
  }
}

const onDragStart = async (row: PlayableRow, event: DragEvent) => {
  // If the user is dragging an unselected row, clear the current selection.
  if (!row.selected) {
    clearSelection()
    row.selected = true
    await nextTick()
  }

  // Add "dragging" class to the wrapper so that we can disable pointer events on child elements.
  // This prevents dragleave events from firing when the user drags the mouse over the child elements.
  wrapper.value?.classList.add('dragging')

  startDragging(event, selectedPlayables.value)
}

const onDragOver = throttle((event: DragEvent) => {
  if (!config.reorderable) return

  if (acceptsDrop(event)) {
    const target = event.target as HTMLElement
    const rect = target.getBoundingClientRect()
    const midPoint = rect.top + rect.height / 2
    target.classList.remove('dragover-top', 'dragover-bottom')
    target.classList.add('droppable', event.clientY < midPoint ? 'dragover-top' : 'dragover-bottom')
  }

  return false
}, 50)

const onDrop = (row: PlayableRow, event: DragEvent) => {
  if (!config.reorderable || !getDroppedData(event) || !selectedPlayables.value.length) {
    wrapper.value?.classList.remove('dragging')
    return onDragLeave(event)
  }

  wrapper.value?.classList.remove('dragging')

  if (!rowInSelectedRange(row)) {
    emit(
      'reorder',
      row.playable,
      (event.target as HTMLElement).classList.contains('dragover-bottom') ? 'after' : 'before'
    )
  }

  return onDragLeave(event)
}

const onDragLeave = (event: DragEvent) => {
  (event.target as HTMLElement).closest('.song-item')?.classList.remove('droppable', 'dragover-top', 'dragover-bottom')
  return false
}

const onDragEnd = () => wrapper.value?.classList.remove('dragging')

const rowInSelectedRange = (row: PlayableRow) => {
  if (!row.selected) return false

  const index = findIndex(rows.value, ({ playable }) => playable.id === row.playable.id)
  const firstSelectedIndex = Math.max(0, findIndex(rows.value, ({ selected }) => selected))
  const lastSelectedIndex = Math.max(0, findLastIndex(rows.value, ({ selected }) => selected))

  if (index < firstSelectedIndex || index > lastSelectedIndex) return false

  for (let i = firstSelectedIndex; i <= lastSelectedIndex; ++i) {
    if (!rows.value[i].selected) return false
  }

  return true
}

const openContextMenu = async (row: PlayableRow, event: MouseEvent) => {
  if (!row.selected) {
    clearSelection()
    toggleRow(row)

    // awaiting a next tick so that the selected items are collected properly
    await nextTick()
  }

  eventBus.emit('PLAYABLE_CONTEXT_MENU_REQUESTED', event, selectedPlayables.value)
}

const onPlay = async (playable: Playable) => {
  if (shouldTriggerContinuousPlayback.value) {
    queueStore.replaceQueueWith(getAllPlayablesWithSort())
  }

  await playbackService.play(playable)
}

defineExpose({
  getAllPlayablesWithSort
})

onMounted(() => render())
</script>

<style lang="postcss">
.song-list-wrap {
  .virtual-scroller {
    @apply flex-1;
  }

  &.dragging .song-item * {
    @apply pointer-events-none;
  }

  .song-list-header > span, .song-item > span {
    @apply text-left p-2 align-middle text-ellipsis overflow-hidden whitespace-nowrap;

    &.time {
      @apply basis-20 overflow-visible;
    }

    &.track-number {
      @apply basis-16 pl-6;
    }

    &.album {
      @apply basis-[27%];
    }

    &.collaborator {
      @apply basis-[72px] text-center;
    }

    &.added-at {
      @apply basis-36 text-left;
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
    @apply tracking-widest uppercase cursor-pointer text-k-text-secondary;

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

    .song-item :is(.track-number, .album, .time, .added-at),
    .song-list-header :is(.track-number, .album, .time, .added-at) {
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
