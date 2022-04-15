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
    <table class="song-list-header" :class="mergedConfig.sortable ? 'sortable' : 'unsortable'">
      <thead>
        <tr>
          <th @click="sort('song.track')" class="track-number" v-if="mergedConfig.columns.includes('track')">
            #
            <i class="fa fa-angle-down" v-show="primarySortField === 'song.track' && sortOrder > 0"></i>
            <i class="fa fa-angle-up" v-show="primarySortField === 'song.track' && sortOrder < 0"></i>
          </th>
          <th @click="sort('song.title')" class="title" v-if="mergedConfig.columns.includes('title')">
            Title
            <i class="fa fa-angle-down" v-show="primarySortField === 'song.title' && sortOrder > 0"></i>
            <i class="fa fa-angle-up" v-show="primarySortField === 'song.title' && sortOrder < 0"></i>
          </th>
          <th
            @click="sort(['song.album.artist.name', 'song.album.name', 'song.track'])"
            class="artist"
            v-if="mergedConfig.columns.includes('artist')"
          >
            Artist
            <i class="fa fa-angle-down" v-show="primarySortField === 'song.album.artist.name' && sortOrder > 0"></i>
            <i class="fa fa-angle-up" v-show="primarySortField === 'song.album.artist.name' && sortOrder < 0"></i>
          </th>
          <th
            @click="sort(['song.album.name', 'song.track'])"
            class="album"
            v-if="mergedConfig.columns.includes('album')"
          >
            Album
            <i class="fa fa-angle-down" v-show="primarySortField === 'song.album.name' && sortOrder > 0"></i>
            <i class="fa fa-angle-up" v-show="primarySortField === 'song.album.name' && sortOrder < 0"></i>
          </th>
          <th @click="sort('song.length')" class="time" v-if="mergedConfig.columns.includes('length')">
            Time
            <i class="fa fa-angle-down" v-show="primarySortField === 'song.length' && sortOrder > 0"></i>
            <i class="fa fa-angle-up" v-show="primarySortField === 'song.length' && sortOrder < 0"></i>
          </th>
          <th class="favorite"></th>
          <th class="play"></th>
        </tr>
      </thead>
    </table>

    <virtual-scroller
      class="scroller"
      content-tag="table"
      :items="songProxies"
      item-height="35"
      key-field="song.id"
    >
      <template slot-scope="props">
        <song-item :item="props.item" :columns="mergedConfig.columns" />
      </template>
    </virtual-scroller>
  </div>
</template>

<script lang="ts">
import isMobile from 'ismobilejs'

import Vue, { PropOptions } from 'vue'
import { VirtualScroller } from 'vue-virtual-scroller'
import { orderBy, eventBus, startDragging, $ } from '@/utils'
import { playlistStore, queueStore, favoriteStore } from '@/stores'
import { playback } from '@/services'
import router from '@/router'
import { SongListRowComponent } from 'koel/types/ui'

export type SongListType = 'all-songs'
  | 'queue'
  | 'playlist'
  | 'favorites'
  | 'recently-played'
  | 'artist'
  | 'album'
  | 'search-results'

export type SongListColumn = 'track' | 'title' | 'album' | 'artist' | 'length'

type SortField = 'song.track'
  | 'song.disc'
  | 'song.title'
  | 'song.album.artist.name'
  | 'song.album.name'
  | 'song.length'

export interface SongListConfig {
  sortable: boolean
  columns: SongListColumn[]
}

const enum SortOrder {
  Asc = 1,
  Desc = -1,
  None = 0
}

export default Vue.extend({
  name: 'song-list',

  props: {
    items: {
      type: Array,
      required: true
    } as PropOptions<Song[]>,

    type: {
      type: String,
      default: 'all-songs'
    } as PropOptions<SongListType>,

    config: {
      type: Object,
      default: (): Partial<SongListConfig> => ({})
    } as PropOptions<Partial<SongListConfig>>,

    playlist: {
      type: Object
    } as PropOptions<Playlist>
  },

  components: {
    VirtualScroller,
    SongItem: () => import('@/components/song/item.vue')
  },

  data: () => ({
    lastSelectedRow: null as unknown as SongListRowComponent,
    sortFields: [] as SortField[],
    sortOrder: SortOrder.None,
    songProxies: [] as SongProxy[]
  }),

  watch: {
    items (): void {
      this.render()
    },

    selectedSongs (val: Song[]): void {
      eventBus.emit('SET_SELECTED_SONGS', val, this.$parent)
    }
  },

  computed: {
    allowSongReordering (): boolean {
      return this.type === 'queue'
    },

    selectedSongs (): Song[] {
      return this.songProxies.filter(row => row.selected).map(row => row.song)
    },

    mergedConfig (): SongListConfig {
      return Object.assign({
        sortable: true,
        columns: ['track', 'title', 'artist', 'album', 'length']
      }, this.config)
    },

    primarySortField (): string | null {
      return this.sortFields.length === 0 ? null : this.sortFields[0]
    }
  },

  methods: {
    render (): void {
      if (!this.mergedConfig.sortable) {
        this.sortFields = []
      }

      this.songProxies = this.generateSongProxies()
      this.sort(this.sortFields, this.sortOrder)
    },

    /**
     * Since song objects themselves are shared by all song lists, we can't use them directly to
     * determine their selection status (selected/unselected). Therefore, for each song list, we
     * maintain an array of "song proxies," each containing the song itself and the "selected" flag.
     * To comply with virtual-scroller, a "type" attribute also presents.
     */
    generateSongProxies (): SongProxy[] {
      // Since this method re-generates the song wrappers, we need to keep track of  the
      // selected songs manually.
      const selectedSongIds = this.selectedSongs.map((song: Song): string => song.id)

      return this.items.map((song): SongProxy => ({
        song,
        selected: selectedSongIds.includes(song.id)
      }))
    },

    sort (field: SortField | SortField[] = [], order: SortOrder | null = null) {
      // there are certain circumstances where sorting is simply disallowed, e.g. in Queue
      if (!this.mergedConfig.sortable) {
        return
      }

      this.sortFields = ([] as SortField[]).concat(field)

      if (!this.sortFields.length && ['album', 'artist'].includes(this.type)) {
        // by default, sort Album/Artist by track numbers for a more friendly UX
        this.sortFields.push('song.track')
        order = SortOrder.Asc
      }

      if (this.sortFields.includes('song.track') && !this.sortFields.includes('song.disc')) {
        // Track numbers should always go in conjunction with disc numbers.
        this.sortFields.push('song.disc')
      }

      this.sortOrder = order === null ? this.nextSortOrder() : order

      this.songProxies = this.sortOrder === SortOrder.None
        ? this.generateSongProxies()
        : orderBy(this.songProxies, this.sortFields, this.sortOrder)
    },

    nextSortOrder (): SortOrder {
      if (this.sortOrder === SortOrder.None) return SortOrder.Asc
      if (this.sortOrder === SortOrder.Asc) return SortOrder.Desc
      return SortOrder.None
    },

    handleDelete (): void {
      if (!this.selectedSongs.length) {
        return
      }

      switch (this.type) {
        case 'queue':
          queueStore.unqueue(this.selectedSongs)
          break

        case 'favorites':
          favoriteStore.unlike(this.selectedSongs)
          break

        case 'playlist':
          playlistStore.removeSongs(this.playlist, this.selectedSongs)
          break

        default:
          return
      }

      this.clearSelection()
    },

    handleEnter (event: DragEvent): void {
      if (!this.selectedSongs.length) {
        return
      }

      if (this.selectedSongs.length === 1) {
        // Just play the song
        playback.play(this.selectedSongs[0])
        return
      }

      switch (this.type) {
        case 'queue':
          // Play the first song selected if we're in Queue screen.
          playback.play(this.selectedSongs[0])
          break

        default:
          //
          // --------------------------------------------------------------------
          // For other screens, follow this map:
          //
          //  • Enter: Queue songs to bottom
          //  • Shift+Enter: Queues song to top
          //  • Cmd/Ctrl+Enter: Queues song to bottom and play the first selected song
          //  • Cmd/Ctrl+Shift+Enter: Queue songs to top and play the first queued song
          // --------------------------------------------------------------------
          //
          if (event.shiftKey) {
            queueStore.queueToTop(this.selectedSongs)
          } else {
            queueStore.queue(this.selectedSongs)
          }

          if (event.ctrlKey || event.metaKey) {
            playback.play(this.selectedSongs[0])
          }

          router.go('queue')

          break
      }
    },

    handleA (event: KeyboardEvent): void {
      if (!event.metaKey && !event.ctrlKey) {
        return
      }

      this.selectAllRows()
    },

    /**
     * Select all (filtered) rows in the current list.
     */
    selectAllRows (): void {
      this.songProxies.forEach(row => (row.selected = true))
    },

    rowClicked (rowVm: SongListRowComponent, event: MouseEvent): void {
      // If we're on a touch device, or if Ctrl/Cmd key is pressed, just toggle selection.
      if (isMobile.any) {
        this.toggleRow(rowVm)
        return
      }

      if (event.ctrlKey || event.metaKey) {
        this.toggleRow(rowVm)
      }

      if (event.button === 0) {
        if (!(event.ctrlKey || event.metaKey || event.shiftKey)) {
          this.clearSelection()
          this.toggleRow(rowVm)
        }

        if (event.shiftKey && this.lastSelectedRow) {
          this.selectRowsBetween(this.lastSelectedRow, rowVm)
        }
      }
    },

    toggleRow (rowVm: SongListRowComponent): void {
      rowVm.item.selected = !rowVm.item.selected
      this.lastSelectedRow = rowVm
    },

    selectRowsBetween (firstRowVm: SongListRowComponent, secondRowVm: SongListRowComponent): void {
      const indexes = [
        this.songProxies.indexOf(firstRowVm.item),
        this.songProxies.indexOf(secondRowVm.item)
      ]

      indexes.sort((a, b) => a - b)

      for (let i = indexes[0]; i <= indexes[1]; ++i) {
        this.songProxies[i].selected = true
      }
    },

    clearSelection (): void {
      this.songProxies.forEach((row: SongProxy): void => {
        row.selected = false
      })
    },

    /**
     * Enable dragging songs by capturing the dragstart event on a table row.
     * Even though the event is triggered on one row only, we'll collect other
     * selected rows, if any, as well.
     */
    dragStart (rowVm: SongListRowComponent, event: DragEvent): void {
      // If the user is dragging an unselected row, clear the current selection.
      if (!rowVm.item.selected) {
        this.clearSelection()
        rowVm.item.selected = true
      }

      startDragging(event, this.selectedSongs, 'Song')
    },

    /**
     * Add a "droppable" class and set the drop effect when other songs are dragged over a row.
     */
    allowDrop (event: DragEvent) {
      if (!this.allowSongReordering) {
        return
      }

      $.addClass((event.target as Element).parentElement, 'droppable')
      event.dataTransfer!.dropEffect = 'move'

      return false
    },

    /**
     * Perform reordering songs upon dropping if the current song list is of type Queue.
     */
    handleDrop (rowVm: SongListRowComponent, event: DragEvent): boolean {
      if (
        !this.allowSongReordering ||
        !event.dataTransfer!.getData('application/x-koel.text+plain') ||
        !this.selectedSongs.length
      ) {
        return this.removeDroppableState(event)
      }

      queueStore.move(this.selectedSongs, rowVm.item.song)
      return this.removeDroppableState(event)
    },

    removeDroppableState: (event: DragEvent): boolean => {
      $.removeClass((event.target as Element).parentElement, 'droppable')
      return false
    },

    openContextMenu (rowVm: SongListRowComponent, e: MouseEvent): void {
      // If the user is right-clicking an unselected row,
      // clear the current selection and select it instead.
      if (!rowVm.item.selected) {
        this.clearSelection()
        this.toggleRow(rowVm)
      }

      this.$nextTick((): void => {
        eventBus.emit('SONG_CONTEXT_MENU_REQUESTED', e, this.selectedSongs)
      })
    },

    getAllSongsWithSort (): Song[] {
      return this.songProxies.map(proxy => proxy.song)
    }
  },

  mounted (): void {
    if (this.items) {
      this.render()
    }
  }
})
</script>

<style lang="scss">
.song-list-wrap {
  position: relative;
  padding: 8px 24px;

  .song-list-header {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    background: var(--color-bg-secondary);
    z-index: 1;
    width: 100%;
  }

  table {
    width: 100%;
    table-layout: fixed;
  }

  tr.droppable {
    border-bottom-width: 3px;
    border-bottom-color: var(--color-green);
  }

  td,
  th {
    text-align: left;
    padding: 8px;
    vertical-align: middle;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;

    &.time {
      width: 96px;
      padding-right: 24px;
      text-align: right;
    }

    &.track-number {
      width: 66px;
      padding-left: 24px;
    }

    &.artist {
      width: 23%;
    }

    &.album {
      width: 27%;
    }

    &.favorite {
      width: 36px;
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
  }

  th {
    color: var(--color-text-secondary);
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;

    i {
      color: var(--color-highlight);
      font-size: 1.2rem;
    }
  }

  .unsortable th {
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
    table,
    tbody,
    tr {
      display: block;
    }

    thead,
    tfoot {
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

    tr {
      padding: 8px 32px 8px 4px;
      position: relative;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      color: var(--color-text-secondary);
      width: 100%;
    }

    td {
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
