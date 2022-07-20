<template>
  <div
    ref="wrapper"
    :class="type"
    class="song-list-wrap main-scroll-wrap"
    data-testid="song-list"
    tabindex="0"
    @keydown.delete.prevent.stop="handleDelete"
    @keydown.enter.prevent.stop="handleEnter"
    @keydown.a.prevent="handleA"
  >
    <div :class="config.sortable ? 'sortable' : 'unsortable'" class="song-list-header">
      <span
        v-if="config.columns.includes('track')"
        class="track-number"
        data-testid="header-track-number"
        @click="sort('track')"
      >
        #
        <icon v-if="sortField === 'track' && sortOrder === 'asc'" :icon="faAngleDown" class="text-highlight"/>
        <icon v-if="sortField === 'track' && sortOrder === 'desc'" :icon="faAngleUp" class="text-highlight"/>
      </span>
      <span
        v-if="config.columns.includes('title')"
        class="title"
        data-testid="header-title"
        @click="sort('title')"
      >
        Title
        <icon v-if="sortField === 'title' && sortOrder === 'asc'" :icon="faAngleDown" class="text-highlight"/>
        <icon v-if="sortField === 'title' && sortOrder === 'desc'" :icon="faAngleUp" class="text-highlight"/>
      </span>
      <span
        v-if="config.columns.includes('artist')"
        class="artist"
        data-testid="header-artist"
        @click="sort('artist_name')"
      >
        Artist
        <icon v-if="sortField === 'artist_name' && sortOrder === 'asc'" :icon="faAngleDown" class="text-highlight"/>
        <icon v-if="sortField === 'artist_name' && sortOrder === 'desc'" :icon="faAngleUp" class="text-highlight"/>
      </span>
      <span
        v-if="config.columns.includes('album')"
        class="album"
        data-testid="header-album"
        @click="sort('album_name')"
      >
        Album
        <icon v-if="sortField === 'album_name' && sortOrder === 'asc'" :icon="faAngleDown" class="text-highlight"/>
        <icon v-if="sortField === 'album_name' && sortOrder === 'desc'" :icon="faAngleUp" class="text-highlight"/>
      </span>
      <span
        v-if="config.columns.includes('length')"
        class="time"
        data-testid="header-length"
        @click="sort('length')"
      >
        <icon v-if="sortField === 'length' && sortOrder === 'asc'" :icon="faAngleDown" class="text-highlight"/>
        <icon v-if="sortField === 'length' && sortOrder === 'desc'" :icon="faAngleUp" class="text-highlight"/>
        &nbsp;
        <icon :icon="faClock" class="duration-header"/>
      </span>
      <span class="favorite"></span>
      <span class="play"></span>
    </div>

    <VirtualScroller
      v-slot="{ item }"
      :item-height="35"
      :items="songRows"
      @scroll="onScroll"
      @scrolled-to-end="$emit('scrolled-to-end')"
    >
      <SongListItem
        :key="item.song.id"
        :columns="config.columns"
        :item="item"
        draggable="true"
        @click="rowClicked(item, $event)"
        @dragleave="removeDroppableState"
        @dragstart="rowDragStart(item, $event)"
        @dragenter.prevent="allowDrop"
        @dragover.prevent
        @drop.stop.prevent="handleDrop(item, $event)"
        @contextmenu.prevent="openContextMenu(item, $event)"
      />
    </VirtualScroller>
  </div>
</template>

<script lang="ts" setup>
import { faAngleDown, faAngleUp } from '@fortawesome/free-solid-svg-icons'
import { faClock } from '@fortawesome/free-regular-svg-icons'
import isMobile from 'ismobilejs'
import { findIndex } from 'lodash'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { $, eventBus, requireInjection, startDragging } from '@/utils'
import {
  SelectedSongsKey,
  SongListConfigKey,
  SongListSortFieldKey,
  SongListSortOrderKey,
  SongListTypeKey,
  SongsKey
} from '@/symbols'

import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import SongListItem from '@/components/song/SongListItem.vue'

const emit = defineEmits(['press:enter', 'press:delete', 'reorder', 'sort', 'scroll-breakpoint', 'scrolled-to-end'])

const [items] = requireInjection(SongsKey)
const [type] = requireInjection(SongListTypeKey)
const [selectedSongs, setSelectedSongs] = requireInjection(SelectedSongsKey)
const [sortField, setSortField] = requireInjection(SongListSortFieldKey)
const [sortOrder, setSortOrder] = requireInjection(SongListSortOrderKey)

const lastSelectedRow = ref<SongRow>()
const sortFields = ref<SongListSortField[]>([])
const songRows = ref<SongRow[]>([])

const allowReordering = type.value === 'queue'

watch(songRows, () => setSelectedSongs(songRows.value.filter(row => row.selected).map(row => row.song)), { deep: true })

const config = computed((): SongListConfig => {
  const [injectedConfig] = requireInjection(SongListConfigKey, {})

  return Object.assign({
    sortable: true,
    columns: ['track', 'title', 'artist', 'album', 'length']
  }, injectedConfig)
})

let lastScrollTop = 0

const onScroll = e => {
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

  return items.value.map(song => ({
    song,
    selected: selectedSongIds.includes(song.id)
  }))
}

const sort = (field: SongListSortField) => {
  // there are certain circumstances where sorting is simply disallowed, e.g. in Queue
  if (!config.value.sortable) {
    return
  }

  setSortField(field)
  setSortOrder(sortOrder.value === 'asc' ? 'desc' : 'asc')

  emit('sort', field, sortOrder.value)
}

const render = () => {
  config.value.sortable || (sortFields.value = [])
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

/**
 * Enable dragging songs by capturing the dragstart event on a table row.
 * Even though the event is triggered on one row only, we'll collect other
 * selected rows, if any, as well.
 */
const rowDragStart = (row: SongRow, event: DragEvent) => {
  // If the user is dragging an unselected row, clear the current selection.
  if (!row.selected) {
    clearSelection()
    row.selected = true
  }

  startDragging(event, selectedSongs.value, 'Song')
}

/**
 * Add a "droppable" class and set the drop effect when other songs are dragged over a row.
 */
const allowDrop = (event: DragEvent) => {
  if (!allowReordering) return

  $.addClass((event.target as Element).parentElement, 'droppable')
  event.dataTransfer!.dropEffect = 'move'

  return false
}

const handleDrop = (item: SongRow, event: DragEvent) => {
  if (
    !allowReordering ||
    !event.dataTransfer!.getData('application/x-koel.text+plain') ||
    !selectedSongs.value.length
  ) {
    return removeDroppableState(event)
  }

  emit('reorder', item.song)
  return removeDroppableState(event)
}

const removeDroppableState = (event: DragEvent) => {
  $.removeClass((event.target as Element).parentElement, 'droppable')
  return false
}

const openContextMenu = async (row: SongRow, event: MouseEvent) => {
  // If the user is right-clicking an unselected row,
  // clear the current selection and select it instead.
  if (!row.selected) {
    clearSelection()
    toggleRow(row)
  }

  await nextTick()

  eventBus.emit('SONG_CONTEXT_MENU_REQUESTED', event, selectedSongs.value)
}

defineExpose({
  getAllSongsWithSort,
  sort
})

onMounted(() => render())
</script>

<style lang="scss">
.song-list-wrap {
  position: relative;
  padding: 0 !important;
  display: flex;
  flex-direction: column;

  .song-list-header {
    background: var(--color-bg-secondary);
    z-index: 1;
    display: flex;
  }

  div.droppable {
    border-bottom-width: 3px;
    border-bottom-color: var(--color-green);
  }

  .song-list-header span, .song-item span {
    text-align: left;
    padding: 8px;
    vertical-align: middle;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;

    &.time {
      flex-basis: 96px;
      padding-right: 24px;
      text-align: right;
    }

    &.track-number {
      flex-basis: 66px;
      padding-left: 24px;
    }

    &.artist {
      flex-basis: 23%;
    }

    &.album {
      flex-basis: 27%;
    }

    &.favorite {
      flex-basis: 36px;
    }

    &.play {
      display: none;

      @media (hover: none) {
        display: block;
        position: absolute;
        top: 8px;
        right: 4px;
      }
    }

    &.title {
      flex: 1;
    }
  }

  .song-list-header {
    color: var(--color-text-secondary);
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;

    i:not(.duration-header) {
      color: var(--color-highlight);
      font-size: 1.2rem;
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
    .song-list-header {
      display: none;
    }

    .scroller {
      top: 0;

      .item-container {
        left: 12px;
        right: 12px;
        width: calc(200vw - 24px);
      }
    }

    .song-item {
      padding: 8px 32px 8px 4px;
      position: relative;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      color: var(--color-text-secondary);
      width: 200%;
    }

    .song-item span {
      display: none;
      padding: 0;
      vertical-align: bottom;
      color: var(--color-text-primary);

      &.artist, &.title {
        display: inline;
      }

      &.artist {
        color: var(--color-text-secondary);
        font-size: 0.9rem;
        padding: 0 4px;
      }
    }
  }
}
</style>
