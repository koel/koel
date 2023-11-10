<template>
  <div
    ref="wrapper"
    class="song-list-wrap"
    data-testid="song-list"
    tabindex="0"
    @keydown.delete.prevent.stop="handleDelete"
    @keydown.enter.prevent.stop="handleEnter"
    @keydown.a.prevent="handleA"
  >
    <div :class="config.sortable ? 'sortable' : 'unsortable'" class="song-list-header">
      <span
        class="track-number"
        data-testid="header-track-number"
        role="button"
        title="Sort by track number"
        @click="sort('track')"
      >
        #
        <template v-if="config.sortable">
          <Icon v-if="sortField === 'track' && sortOrder === 'asc'" :icon="faCaretDown" class="text-highlight" />
          <Icon v-if="sortField === 'track' && sortOrder === 'desc'" :icon="faCaretUp" class="text-highlight" />
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
          <Icon v-if="sortField === 'title' && sortOrder === 'asc'" :icon="faCaretDown" class="text-highlight" />
          <Icon v-if="sortField === 'title' && sortOrder === 'desc'" :icon="faCaretUp" class="text-highlight" />
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
          <Icon v-if="sortField === 'album_name' && sortOrder === 'asc'" :icon="faCaretDown" class="text-highlight" />
          <Icon v-if="sortField === 'album_name' && sortOrder === 'desc'" :icon="faCaretUp" class="text-highlight" />
        </template>
      </span>
      <span
        class="time"
        data-testid="header-length"
        role="button"
        title="Sort by song duration"
        @click="sort('length')"
      >
        Time
        <template v-if="config.sortable">
          <Icon v-if="sortField === 'length' && sortOrder === 'asc'" :icon="faCaretDown" class="text-highlight" />
          <Icon v-if="sortField === 'length' && sortOrder === 'desc'" :icon="faCaretUp" class="text-highlight" />
        </template>
      </span>
      <span class="extra">
        <SongListSorter v-if="config.sortable" :field="sortField" :order="sortOrder" @sort="sort" />
      </span>
    </div>

    <VirtualScroller
      v-slot="{ item }"
      :item-height="64"
      :items="filteredSongRows"
      @scroll="onScroll"
      @scrolled-to-end="$emit('scrolled-to-end')"
    >
      <SongListItem
        :key="item.song.id"
        :item="item"
        draggable="true"
        @click="rowClicked(item, $event)"
        @dragleave="onDragLeave"
        @dragstart="onDragStart(item, $event)"
        @dragenter.prevent="onDragEnter"
        @dragover.prevent
        @drop.prevent="onDrop(item, $event)"
        @dragend.prevent="onDragEnd"
        @contextmenu.prevent="openContextMenu(item, $event)"
      />
    </VirtualScroller>
  </div>
</template>

<script lang="ts" setup>
import { findIndex } from 'lodash'
import isMobile from 'ismobilejs'
import { faCaretDown, faCaretUp } from '@fortawesome/free-solid-svg-icons'
import { computed, nextTick, onMounted, Ref, ref, watch } from 'vue'
import { eventBus, requireInjection } from '@/utils'
import { useDraggable, useDroppable } from '@/composables'
import {
  SelectedSongsKey,
  SongListConfigKey,
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
  (e: 'reorder', song: Song): void,
  (e: 'sort', field: SongListSortField, order: SortOrder): void,
  (e: 'scroll-breakpoint', direction: 'up' | 'down'): void,
  (e: 'scrolled-to-end'): void,
}>()

const [items] = requireInjection<[Ref<Song[]>]>(SongsKey)
const [selectedSongs, setSelectedSongs] = requireInjection<[Ref<Song[]>, Closure]>(SelectedSongsKey)
const [sortField, setSortField] = requireInjection<[Ref<SongListSortField>, Closure]>(SongListSortFieldKey)
const [sortOrder, setSortOrder] = requireInjection<[Ref<SortOrder>, Closure]>(SongListSortOrderKey)
const [config] = requireInjection<[Partial<SongListConfig>]>(SongListConfigKey, [{}])

const filterKeywords = requireInjection(SongListFilterKeywordsKey, ref(''))

const wrapper = ref<HTMLElement>()
const lastSelectedRow = ref<SongRow>()
const sortFields = ref<SongListSortField[]>([])
const songRows = ref<SongRow[]>([])

watch(songRows, () => setSelectedSongs(songRows.value.filter(row => row.selected).map(row => row.song)), { deep: true })

const filteredSongRows = computed(() => {
  const keywords = filterKeywords.value.trim().toLowerCase()

  if (!keywords) {
    return songRows.value
  }

  return songRows.value.filter(row => {
    const song = row.song

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

const rowClicked = (row: SongRow, event: MouseEvent) => {
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

const onDragStart = (row: SongRow, event: DragEvent) => {
  // If the user is dragging an unselected row, clear the current selection.
  if (!row.selected) {
    clearSelection()
    row.selected = true
  }

  // Add "dragging" class to the wrapper so that we can disable pointer events on child elements.
  // This prevents dragleave events from firing when the user drags the mouse over the child elements.
  wrapper.value?.classList.add('dragging')

  startDragging(event, selectedSongs.value)
}

const onDragEnter = (event: DragEvent) => {
  if (!config.reorderable) return

  if (acceptsDrop(event)) {
    (event.target as HTMLElement).closest('.song-item')?.classList.add('droppable')
  }

  return false
}

const onDrop = (item: SongRow, event: DragEvent) => {
  if (!config.reorderable || !getDroppedData(event) || !selectedSongs.value.length) {
    wrapper.value?.classList.remove('dragging')
    return onDragLeave(event)
  }

  wrapper.value?.classList.remove('dragging')

  emit('reorder', item.song)
  return onDragLeave(event)
}

const onDragLeave = (event: DragEvent) => {
  (event.target as HTMLElement).closest('.song-item')?.classList.remove('droppable')
  return false
}

const onDragEnd = () => wrapper.value?.classList.remove('dragging')

const openContextMenu = async (row: SongRow, event: MouseEvent) => {
  if (!row.selected) {
    clearSelection()
    toggleRow(row)

    // awaiting a next tick so that the selected songs are collected properly
    await nextTick()
  }

  eventBus.emit('SONG_CONTEXT_MENU_REQUESTED', event, selectedSongs.value)
}

defineExpose({
  getAllSongsWithSort
})

onMounted(() => render())
</script>

<style lang="scss">
.song-list-wrap {
  position: relative;
  display: flex;
  flex-direction: column;
  overflow: auto;
  flex: 1;

  @media screen and (max-width: 768px) {
    padding: 0 12px;
  }

  .song-list-header {
    background: var(--color-bg-secondary);
    display: flex;
    z-index: 2; // fix stack-context related issue when e.g., footer would cover the sort context menu
  }

  &.dragging .song-item * {
    pointer-events: none;
  }

  .droppable {
    position: relative;
    box-shadow: 0 3px 0 var(--color-green);
  }

  .song-list-header > span, .song-item > span {
    text-align: left;
    padding: 8px;
    vertical-align: middle;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;

    &.time {
      flex-basis: 64px;
      overflow: visible;
    }

    &.track-number {
      flex-basis: 66px;
      padding-left: 24px;
    }

    &.album {
      flex-basis: 27%;
    }

    &.extra {
      flex-basis: 36px;
    }

    &.play {
      display: none;

      @media (hover: none) {
        display: block;
      }
    }

    &.title-artist {
      flex: 1;
    }

    &.extra {
      text-align: center;
    }
  }

  .song-list-header {
    color: var(--color-text-secondary);
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;

    .extra {
      padding-left: 0;
      padding-right: 0;
    }
  }

  .unsortable span {
    cursor: default;
  }

  .scroller {
    overflow: auto;
    position: absolute;
    top: 35px;
    left: 0;
    bottom: 0;
    right: 0;
    overflow-y: scroll;
    -webkit-overflow-scrolling: touch;

    .item-container {
      position: absolute;
      left: 0;
      right: 0;
      min-height: 200%;
    }

    .item {
      margin-bottom: 0;
    }
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

    .song-item :is(.track-number, .album, .time),
    .song-list-header :is(.track-number, .album, .time) {
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

  .virtual-scroller {
    flex: 1;
  }
}
</style>
