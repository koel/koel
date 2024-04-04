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
        title="Sort by album"
        @click="sort('album_name')"
      >
        Album
        <template v-if="config.sortable">
          <Icon v-if="sortField === 'album_name' && sortOrder === 'asc'" :icon="faCaretDown" class="text-k-highlight" />
          <Icon v-if="sortField === 'album_name' && sortOrder === 'desc'" :icon="faCaretUp" class="text-k-highlight" />
        </template>
      </span>
      <template v-if="config.collaborative">
        <span class="collaborator">User</span>
        <span class="added-at">Added</span>
      </template>
      <span
        class="time"
        data-testid="header-length"
        role="button"
        title="Sort by song duration"
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
          :has-custom-sort="config.hasCustomSort"
          :field="sortField"
          :order="sortOrder"
          @sort="sort"
        />
      </span>
    </div>

    <VirtualScroller
      v-slot="{ item }: { item: SongRow }"
      :item-height="64"
      :items="filteredSongRows"
      @scroll="onScroll"
      @scrolled-to-end="$emit('scrolled-to-end')"
    >
      <SongListItem
        :key="item.song.id"
        :item="item"
        draggable="true"
        @click="onClick(item, $event)"
        @dragleave="onDragLeave"
        @dragstart="onDragStart(item, $event)"
        @dragover.prevent="onDragOver"
        @drop.prevent="onDrop(item, $event)"
        @dragend.prevent="onDragEnd"
        @contextmenu.prevent="openContextMenu(item, $event)"
        @play="onSongPlay(item.song)"
      />
    </VirtualScroller>
  </div>
</template>

<script lang="ts" setup>
import { findIndex, findLastIndex, throttle } from 'lodash'
import isMobile from 'ismobilejs'
import { faCaretDown, faCaretUp } from '@fortawesome/free-solid-svg-icons'
import { computed, nextTick, onMounted, Ref, ref, watch } from 'vue'
import { eventBus, requireInjection } from '@/utils'
import { preferenceStore, queueStore } from '@/stores'
import { useDraggable, useDroppable } from '@/composables'
import { playbackService } from '@/services'
import {
  SelectedSongsKey,
  SongListConfigKey,
  SongListContextKey,
  SongListFilterKeywordsKey,
  SongListSortFieldKey,
  SongListSortOrderKey,
  SongsKey
} from '@/symbols'

import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import SongListItem from '@/components/song/SongListItem.vue'
import SongListSorter from '@/components/song/SongListSorter.vue'

const { startDragging } = useDraggable('songs')
const { getDroppedData, acceptsDrop } = useDroppable(['songs'])

const emit = defineEmits<{
  (e: 'press:enter', event: KeyboardEvent): void,
  (e: 'press:delete'): void,
  (e: 'reorder', song: Song, type: MoveType): void,
  (e: 'sort', field: SongListSortField, order: SortOrder): void,
  (e: 'scroll-breakpoint', direction: 'up' | 'down'): void,
  (e: 'scrolled-to-end'): void,
}>()

const [items] = requireInjection<[Ref<Song[]>]>(SongsKey)
const [selectedSongs, setSelectedSongs] = requireInjection<[Ref<Song[]>, Closure]>(SelectedSongsKey)
const [sortField, setSortField] = requireInjection<[Ref<SongListSortField>, Closure]>(SongListSortFieldKey)
const [sortOrder, setSortOrder] = requireInjection<[Ref<SortOrder>, Closure]>(SongListSortOrderKey)
const [config] = requireInjection<[Partial<SongListConfig>]>(SongListConfigKey, [{}])
const [context] = requireInjection<[SongListContext]>(SongListContextKey)

const filterKeywords = requireInjection(SongListFilterKeywordsKey, ref(''))

const wrapper = ref<HTMLElement>()
const lastSelectedRow = ref<SongRow>()
const sortFields = ref<SongListSortField[]>([])
const songRows = ref<SongRow[]>([])

const shouldTriggerContinuousPlayback = computed(() => {
  return preferenceStore.continuous_playback
    && typeof context.type !== 'undefined'
    && ['Playlist', 'Album', 'Artist', 'Genre'].includes(context.type)
})

watch(
  songRows,
  () => setSelectedSongs(songRows.value.filter(({ selected }) => selected).map(({ song }) => song)),
  { deep: true }
)

const filteredSongRows = computed<SongRow[]>(() => {
  const keywords = filterKeywords.value.trim().toLowerCase()

  if (!keywords) {
    return songRows.value
  }

  return songRows.value.filter(({ song }) => {
    return (
      song.title.toLowerCase().includes(keywords) ||
      song.artist_name.toLowerCase().includes(keywords) ||
      song.album_artist_name.toLowerCase().includes(keywords) ||
      song.album_name.toLowerCase().includes(keywords)
    )
  })
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
 * Since song objects themselves are shared by all song lists, we can't use them directly to
 * determine their selection status (selected/unselected). Therefore, for each song list, we
 * maintain an array of "song rows," each containing the song itself and the "selected" flag.
 */
const generateSongRows = () => {
  // Since this method re-generates the song wrappers, we need to keep track of  the
  // selected songs manually.
  const selectedSongIds = selectedSongs.value.map(song => song.id)

  return items.value.map<SongRow>(song => ({
    song,
    selected: selectedSongIds.includes(song.id)
  }))
}

const sort = (field: SongListSortField) => {
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
  songRows.value = generateSongRows()
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
const selectAllRows = () => songRows.value.forEach(row => (row.selected = true))
const clearSelection = () => songRows.value.forEach(row => (row.selected = false))
const handleA = (event: KeyboardEvent) => (event.ctrlKey || event.metaKey) && selectAllRows()
const getAllSongsWithSort = () => songRows.value.map(row => row.song)

const onClick = (row: SongRow, event: MouseEvent) => {
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

const toggleRow = (row: SongRow) => {
  row.selected = !row.selected
  lastSelectedRow.value = row
}

const selectRowsBetween = (first: SongRow, second: SongRow) => {
  const firstIndex = Math.max(0, findIndex(songRows.value, row => row.song.id === first.song.id))
  const secondIndex = Math.max(0, findIndex(songRows.value, row => row.song.id === second.song.id))
  const indexes = [firstIndex, secondIndex]
  indexes.sort((a, b) => a - b)

  for (let i = indexes[0]; i <= indexes[1]; ++i) {
    songRows.value[i].selected = true
  }
}

const onDragStart = async (row: SongRow, event: DragEvent) => {
  // If the user is dragging an unselected row, clear the current selection.
  if (!row.selected) {
    clearSelection()
    row.selected = true
    await nextTick()
  }

  // Add "dragging" class to the wrapper so that we can disable pointer events on child elements.
  // This prevents dragleave events from firing when the user drags the mouse over the child elements.
  wrapper.value?.classList.add('dragging')

  startDragging(event, selectedSongs.value)
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

const onDrop = (row: SongRow, event: DragEvent) => {
  if (!config.reorderable || !getDroppedData(event) || !selectedSongs.value.length) {
    wrapper.value?.classList.remove('dragging')
    return onDragLeave(event)
  }

  wrapper.value?.classList.remove('dragging')

  if (!rowInSelectedRange(row)) {
    emit('reorder', row.song, (event.target as HTMLElement).classList.contains('dragover-bottom') ? 'after' : 'before')
  }

  return onDragLeave(event)
}

const onDragLeave = (event: DragEvent) => {
  (event.target as HTMLElement).closest('.song-item')?.classList.remove('droppable', 'dragover-top', 'dragover-bottom')
  return false
}

const onDragEnd = () => wrapper.value?.classList.remove('dragging')

const rowInSelectedRange = (row: SongRow) => {
  if (!row.selected) return false

  const index = findIndex(songRows.value, ({ song }) => song.id === row.song.id)
  const firstSelectedIndex = Math.max(0, findIndex(songRows.value, ({ selected }) => selected))
  const lastSelectedIndex = Math.max(0, findLastIndex(songRows.value, ({ selected }) => selected))

  if (index < firstSelectedIndex || index > lastSelectedIndex) return false

  for (let i = firstSelectedIndex; i <= lastSelectedIndex; ++i) {
    if (!songRows.value[i].selected) return false
  }

  return true
}

const openContextMenu = async (row: SongRow, event: MouseEvent) => {
  if (!row.selected) {
    clearSelection()
    toggleRow(row)

    // awaiting a next tick so that the selected songs are collected properly
    await nextTick()
  }

  eventBus.emit('SONG_CONTEXT_MENU_REQUESTED', event, selectedSongs.value)
}

const onSongPlay = (song: Song) => {
  if (shouldTriggerContinuousPlayback.value) {
    queueStore.replaceQueueWith(getAllSongsWithSort())
  } else {
    queueStore.queueIfNotQueued(song)
  }

  playbackService.play(song)
}

defineExpose({
  getAllSongsWithSort
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
      @apply basis-16 overflow-visible;
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
