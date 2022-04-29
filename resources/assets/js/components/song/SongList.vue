<template>
  <div
    class="song-list-wrap main-scroll-wrap"
    :class="type"
    ref="wrapper"
    tabindex="0"
    @keydown.delete.prevent.stop="handleDelete"
    @keydown.enter.prevent.stop="handleEnter"
    @keydown.a.prevent="handleA"
  >
    <div class="song-list-header" :class="mergedConfig.sortable ? 'sortable' : 'unsortable'">
      <span @click="sort('song.track')" class="track-number" v-if="mergedConfig.columns.includes('track')">
        #
        <i class="fa fa-angle-down" v-show="primarySortField === 'song.track' && sortOrder === 'Asc'"></i>
        <i class="fa fa-angle-up" v-show="primarySortField === 'song.track' && sortOrder === 'Desc'"></i>
      </span>
      <span @click="sort('song.title')" class="title" v-if="mergedConfig.columns.includes('title')">
        Title
        <i class="fa fa-angle-down" v-show="primarySortField === 'song.title' && sortOrder === 'Asc'"></i>
        <i class="fa fa-angle-up" v-show="primarySortField === 'song.title' && sortOrder === 'Desc'"></i>
      </span>
      <span
        @click="sort(['song.album.artist.name', 'song.album.name', 'song.track'])"
        class="artist"
        v-if="mergedConfig.columns.includes('artist')"
      >
        Artist
        <i class="fa fa-angle-down" v-show="primarySortField === 'song.album.artist.name' && sortOrder === 'Asc'"></i>
        <i class="fa fa-angle-up" v-show="primarySortField === 'song.album.artist.name' && sortOrder === 'Desc'"></i>
      </span>
      <span
        @click="sort(['song.album.name', 'song.track'])"
        class="album"
        v-if="mergedConfig.columns.includes('album')"
      >
        Album
        <i class="fa fa-angle-down" v-show="primarySortField === 'song.album.name' && sortOrder === 'Asc'"></i>
        <i class="fa fa-angle-up" v-show="primarySortField === 'song.album.name' && sortOrder === 'Desc'"></i>
      </span>
      <span @click="sort('song.length')" class="time" v-if="mergedConfig.columns.includes('length')">
        <i class="fa fa-angle-down" v-show="primarySortField === 'song.length' && sortOrder === 'Asc'"></i>
        <i class="fa fa-angle-up" v-show="primarySortField === 'song.length' && sortOrder === 'Desc'"></i>
        &nbsp;<i class="duration-header fa fa-clock-o"></i>
      </span>
      <span class="favorite"></span>
      <span class="play"></span>
    </div>

    <VirtualScroller v-slot="{ item }" :item-height="35" :items="songRows">
      <SongListItem
        :key="item.song.id"
        :columns="mergedConfig.columns"
        :item="item"
        draggable="true"
        @click="rowClicked(item, $event)"
        @dragleave="removeDroppableState"
        @dragstart="rowDragStart(item, $event)"
        @dragenter.prevent="allowDrop"
        @dragover.prevent
        @drop.stop.prevent="handleDrop(item, $event)"
        @contextmenu.stop.prevent="openContextMenu(item, $event)"
      />
    </VirtualScroller>
  </div>
</template>

<script lang="ts">
export default {
  name: 'SongList'
}
</script>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { orderBy } from 'lodash'

import {
  computed,
  defineAsyncComponent,
  getCurrentInstance,
  onMounted,
  ref,
  toRefs,
  watch
} from 'vue'

import { $, eventBus, startDragging, arrayify } from '@/utils'

type SortField = 'song.track'
  | 'song.disc'
  | 'song.title'
  | 'song.album.artist.name'
  | 'song.album.name'
  | 'song.length'

type SortOrder = 'Asc' | 'Desc' | 'None'

const VirtualScroller = defineAsyncComponent(() => import('@/components/ui/VirtualScroller.vue'))
const SongListItem = defineAsyncComponent(() => import('@/components/song/SongListItem.vue'))

const props = withDefaults(
  defineProps<{ items: Song[], type?: SongListType, config?: Partial<SongListConfig> }>(),
  { type: 'all-songs', config: () => ({}) }
)

const { items, type, config } = toRefs(props)

const lastSelectedRow = ref<SongRow>()
const sortFields = ref<SortField[]>([])
const sortOrder = ref<SortOrder>('None')
const songRows = ref<SongRow[]>([])
let initialSortedSongRows: SongRow[] = []

const allowReordering = computed(() => type.value === 'queue')
const selectedSongs = computed(() => songRows.value.filter(row => row.selected).map(row => row.song))
const primarySortField = computed(() => sortFields.value.length === 0 ? null : sortFields.value[0])

const mergedConfig = computed((): SongListConfig => {
  return Object.assign({
    sortable: true,
    columns: ['track', 'title', 'artist', 'album', 'length']
  }, config.value)
})

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

const nextSortOrder = computed<SortOrder>(() => {
  if (sortOrder.value === 'None') return 'Asc'
  if (sortOrder.value === 'Asc') return 'Desc'
  return 'None'
})

const sort = (field: SortField | SortField[] = [], order: SortOrder | null = null) => {
  // there are certain circumstances where sorting is simply disallowed, e.g. in Queue
  if (!mergedConfig.value.sortable) {
    return
  }

  sortFields.value = arrayify(field)

  if (!sortFields.value.length && ['album', 'artist'].includes(type.value)) {
    // by default, sort Album/Artist by track numbers for a more friendly UX
    sortFields.value.push('song.track')
    order = 'Asc'
  }

  if (sortFields.value.includes('song.track') && !sortFields.value.includes('song.disc')) {
    // Track numbers should always go in conjunction with disc numbers.
    sortFields.value.push('song.disc')
  }

  sortOrder.value = order === null ? nextSortOrder.value : order

  songRows.value = sortOrder.value === 'None'
    ? initialSortedSongRows
    : orderBy(songRows.value, sortFields.value, sortOrder.value === 'Desc' ? 'desc' : 'asc')
}

const render = () => {
  mergedConfig.value.sortable || (sortFields.value = [])
  // keep a backup of the initial-sorted rows to revert to it when the sort order becomes "None"
  songRows.value = initialSortedSongRows = generateSongRows()
  sort(sortFields.value, sortOrder.value)
}

watch(items, () => render(), { deep: true })

const vm = getCurrentInstance()
watch(selectedSongs, () => eventBus.emit('SET_SELECTED_SONGS', selectedSongs.value, vm?.parent))

const emit = defineEmits(['press:enter', 'press:delete', 'reorder'])

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
  const indexes = [songRows.value.indexOf(first), songRows.value.indexOf(second)]

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
  if (!allowReordering.value) {
    return
  }

  $.addClass((event.target as Element).parentElement, 'droppable')
  event.dataTransfer!.dropEffect = 'move'

  return false
}

const handleDrop = (item: SongRow, event: DragEvent) => {
  if (
    !allowReordering.value ||
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
      min-height: 100%;
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
        width: calc(100vw - 24px);
      }
    }

    .song-item {
      padding: 8px 32px 8px 4px;
      position: relative;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      color: var(--color-text-secondary);
      width: 100%;
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
