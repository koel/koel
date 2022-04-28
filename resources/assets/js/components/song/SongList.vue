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

    <VirtualScroller v-slot="{ item }" :item-height="35" :items="songProxies">
      <SongListItem :item="item" :columns="mergedConfig.columns" :key="item.song.id"/>
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

import {
  computed,
  defineAsyncComponent,
  getCurrentInstance,
  nextTick,
  onMounted,
  ref,
  toRefs,
  watch
} from 'vue'

import { $, eventBus, orderBy, startDragging, arrayify } from '@/utils'
import { queueStore } from '@/stores'

type SortField = 'song.track'
  | 'song.disc'
  | 'song.title'
  | 'song.album.artist.name'
  | 'song.album.name'
  | 'song.length'

type SortOrder = 'Asc' | 'Desc' | 'None'

interface SongRow {
  props: {
    item: SongProxy
  }
}

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
const songProxies = ref<SongProxy[]>([])

const allowSongReordering = computed(() => type.value === 'queue')
const selectedSongs = computed(() => songProxies.value.filter(row => row.selected).map(row => row.song))
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
 * maintain an array of "song proxies," each containing the song itself and the "selected" flag.
 */
const generateSongProxies = () => {
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

  songProxies.value = sortOrder.value === 'None'
    ? generateSongProxies()
    : orderBy(songProxies.value, sortFields.value, sortOrder.value === 'Desc')
}

const render = () => {
  mergedConfig.value.sortable || (sortFields.value = [])
  songProxies.value = generateSongProxies()
  sort(sortFields.value, sortOrder.value)
}

watch(items, () => render())

const vm = getCurrentInstance()
watch(selectedSongs, () => eventBus.emit('SET_SELECTED_SONGS', selectedSongs.value, vm?.parent))

const emit = defineEmits(['press:enter', 'press:delete'])

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
const selectAllRows = () => songProxies.value.forEach(row => (row.selected = true))
const clearSelection = () => songProxies.value.forEach(row => (row.selected = false))

const handleA = (event: KeyboardEvent) => (event.ctrlKey || event.metaKey) && selectAllRows()

const rowClicked = (rowVm: SongRow, event: MouseEvent) => {
  // If we're on a touch device, or if Ctrl/Cmd key is pressed, just toggle selection.
  if (isMobile.any) {
    toggleRow(rowVm)
    return
  }

  if (event.ctrlKey || event.metaKey) {
    toggleRow(rowVm)
  }

  if (event.button === 0) {
    if (!(event.ctrlKey || event.metaKey || event.shiftKey)) {
      clearSelection()
      toggleRow(rowVm)
    }

    if (event.shiftKey && lastSelectedRow.value) {
      selectRowsBetween(lastSelectedRow.value, rowVm)
    }
  }
}

const toggleRow = (rowVm: SongRow) => {
  rowVm.props.item.selected = !rowVm.props.item.selected
  lastSelectedRow.value = rowVm
}

const selectRowsBetween = (firstRowVm: SongRow, secondRowVm: SongRow) => {
  const indexes = [
    songProxies.value.indexOf(firstRowVm.props.item),
    songProxies.value.indexOf(secondRowVm.props.item)
  ]

  indexes.sort((a, b) => a - b)

  for (let i = indexes[0]; i <= indexes[1]; ++i) {
    songProxies.value[i].selected = true
  }
}

/**
 * Enable dragging songs by capturing the dragstart event on a table row.
 * Even though the event is triggered on one row only, we'll collect other
 * selected rows, if any, as well.
 */
const dragStart = (rowVm: SongRow, event: DragEvent) => {
  // If the user is dragging an unselected row, clear the current selection.
  if (!rowVm.props.item.selected) {
    clearSelection()
    rowVm.props.item.selected = true
  }

  startDragging(event, selectedSongs.value, 'Song')
}

/**
 * Add a "droppable" class and set the drop effect when other songs are dragged over a row.
 */
const allowDrop = (event: DragEvent) => {
  if (!allowSongReordering.value) {
    return
  }

  $.addClass((event.target as Element).parentElement, 'droppable')
  event.dataTransfer!.dropEffect = 'move'

  return false
}

/**
 * Perform reordering songs upon dropping if the current song list is of type Queue.
 */
const handleDrop = (rowVm: SongRow, event: DragEvent) => {
  if (
    !allowSongReordering.value ||
    !event.dataTransfer!.getData('application/x-koel.text+plain') ||
    !selectedSongs.value.length
  ) {
    return removeDroppableState(event)
  }

  queueStore.move(selectedSongs.value, rowVm.props.item.song)
  return removeDroppableState(event)
}

const removeDroppableState = (event: DragEvent) => {
  $.removeClass((event.target as Element).parentElement, 'droppable')
  return false
}

const openContextMenu = async (rowVm: SongRow, event: MouseEvent) => {
  // If the user is right-clicking an unselected row,
  // clear the current selection and select it instead.
  if (!rowVm.props.item.selected) {
    clearSelection()
    toggleRow(rowVm)
  }

  await nextTick()
  eventBus.emit('SONG_CONTEXT_MENU_REQUESTED', event, selectedSongs.value)
}

const getAllSongsWithSort = () => songProxies.value.map(proxy => proxy.song)

defineExpose({
  rowClicked,
  dragStart,
  allowDrop,
  handleDrop,
  removeDroppableState,
  openContextMenu,
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
