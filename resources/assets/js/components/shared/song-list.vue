<template>
  <div class="song-list-wrap main-scroll-wrap" :class="type"
    ref="wrapper"
    tabindex="1"
    @keydown.delete.prevent.stop="handleDelete"
    @keydown.enter.prevent.stop="handleEnter"
    @keydown.a.prevent="handleA"
  >
    <table class="song-list-header">
      <thead>
        <tr>
          <th @click="sort('song.track')" class="track-number">#
            <i class="fa fa-angle-down" v-show="sortKey === 'song.track' && order > 0"/>
            <i class="fa fa-angle-up" v-show="sortKey === 'song.track' && order < 0"/>
          </th>
          <th @click="sort('song.title')" class="title">Title
            <i class="fa fa-angle-down" v-show="sortKey === 'song.title' && order > 0"/>
            <i class="fa fa-angle-up" v-show="sortKey === 'song.title' && order < 0"/>
          </th>
          <th @click="sort(['song.album.artist.name', 'song.album.name', 'song.track'])" class="artist">Artist
            <i class="fa fa-angle-down" v-show="sortingByArtist && order > 0"/>
            <i class="fa fa-angle-up" v-show="sortingByArtist && order < 0"/>
          </th>
          <th @click="sort(['song.album.name', 'song.track'])" class="album">Album
            <i class="fa fa-angle-down" v-show="sortingByAlbum && order > 0"/>
            <i class="fa fa-angle-up" v-show="sortingByAlbum && order < 0"/>
          </th>
          <th @click="sort('song.length')" class="time">Time
            <i class="fa fa-angle-down" v-show="sortKey === 'song.length' && order > 0"/>
            <i class="fa fa-angle-up" v-show="sortKey === 'song.length' && order < 0"/>
          </th>
          <th class="play"></th>
        </tr>
      </thead>
    </table>

    <virtual-scroller
      class="scroller"
      content-tag="table"
      :items="filteredItems"
      item-height="35"
      :renderers="renderers"
      key-field="song.id"
    />

    <song-menu ref="contextMenu" :songs="selectedSongs"/>
  </div>
</template>

<script>
import isMobile from 'ismobilejs'

import { filterBy, orderBy, event, pluralize, $ } from '@/utils'
import { playlistStore, queueStore, songStore, favoriteStore } from '@/stores'
import { playback } from '@/services'
import router from '@/router'
import songItem from './song-item.vue'
import songMenu from './song-menu.vue'

export default {
  name: 'song-list',
  props: {
    items: {
      type: Array,
      required: true
    },
    type: {
      type: String,
      default: 'allSongs',
      validator: value => ['allSongs', 'queue', 'playlist', 'favorites', 'artist', 'album'].includes(value)
    },
    sortable: {
      type: Boolean,
      default: true
    },
    playlist: {
      type: Object
    }
  },

  components: { songItem, songMenu },

  data () {
    return {
      renderers: Object.freeze({
        song: songItem
      }),
      lastSelectedRow: null,
      q: '', // The filter query
      sortKey: '',
      order: -1,
      sortingByAlbum: false,
      sortingByArtist: false,
      songRows: []
    }
  },

  watch: {
    /**
     * Watch the items.
     */
    items () {
      this.render()
    },

    selectedSongs (val) {
      event.emit('setSelectedSongs', val, this.$parent)
    }
  },

  computed: {
    filteredItems () {
      const { keywords, fields } = this.extractSearchData(this.q)
      return keywords ? filterBy(this.songRows, keywords, ...fields) : this.songRows
    },

    /**
     * Determine if the songs in the current list can be reordered by drag-and-dropping.
     * @return {Boolean}
     */
    allowSongReordering () {
      return this.type === 'queue'
    },

    /**
     * Songs that are currently selected (their rows are highlighted).
     * @return {Array.<Object>}
     */
    selectedSongs () {
      return this.filteredItems.filter(row => row.selected).map(row => row.song)
    }
  },

  methods: {
    render () {
      if (this.sortable === false) {
        this.sortKey = ''
      }

      // Update the song count and duration status on parent.
      event.emit('updateMeta', {
        songCount: this.items.length,
        totalLength: songStore.getFormattedLength(this.items)
      }, this.$parent)

      this.generateSongRows()
    },

    /**
     * Generate an array of "song row" or "song wrapper" objects. Since song objects themselves are
     * shared by all song lists, we can't use them directly to determine their selection status
     * (selected/unselected). Therefore, for each song list, we maintain an array of "song row"
     * objects, with each object contain the song itself, and the "selected" flag. In order to
     * comply with virtual-scroller, a "type" attribute also presents.
     */
    generateSongRows () {
      // Since this method re-generates the song wrappers, we need to keep track of  the
      // selected songs manually.
      const selectedSongIds = this.selectedSongs.map(song => song.id)

      this.songRows = this.items.map(song => {
        return {
          song,
          selected: selectedSongIds.includes(song.id),
          type: 'song'
        }
      })
    },

    /**
     * Handle sorting the song list.
     *
     * @param  {String} key The sort key. Can be 'title', 'album', 'artist', or 'length'
     */
    sort (key = null) {
      // there are certain cirscumstances where sorting is simply disallowed, e.g. in Queue
      if (this.sortable === false) {
        return
      }

      if (key) {
        this.sortKey = key
        this.order *= -1
      }

      // if this is an album's song list, default to sorting by track number
      // and additionally sort by disc number
      if (this.type === 'album') {
        this.sortKey = this.sortKey ? this.sortKey : ['song.track']
        this.sortKey = [].concat(this.sortKey)
        if (!this.sortKey.includes('song.disc')) {
          this.sortKey.push('song.disc')
        }
      }

      this.sortingByAlbum = this.sortKey[0] === 'song.album.name'
      this.sortingByArtist = this.sortKey[0] === 'song.album.artist.name'

      this.songRows = orderBy(this.songRows, this.sortKey, this.order)
    },

    /**
     * Execute the corresponding reaction(s) when the user presses Delete.
     */
    handleDelete () {
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
          break
      }

      this.clearSelection()
    },

    /**
     * Execute the corresponding reaction(s) when the user presses Enter.
     *
     * @param {Event} event The keydown event.
     */
    handleEnter (event) {
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
          queueStore.queue(this.selectedSongs, false, event.shiftKey)

          if (event.ctrlKey || event.metaKey) {
            playback.play(this.selectedSongs[0])
          }

          router.go('queue')

          break
      }
    },

    /**
     * Capture A keydown event and select all if applicable.
     *
     * @param {Event} event The keydown event.
     */
    handleA (event) {
      if (!event.metaKey && !event.ctrlKey) {
        return
      }

      this.selectAllRows()
    },

    /**
     * Select all (filtered) rows in the current list.
     */
    selectAllRows () {
      this.filteredItems.forEach(row => {
        row.selected = true
      })
    },

    /**
     * Handle the click event on a row to perform selection.
     *
     * @param  {VueComponent} rowVm
     * @param  {Event} e
     */
    rowClicked (rowVm, event) {
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

    /**
     * Toggle select/unslect a row.
     *
     * @param  {VueComponent} rowVm The song-item component
     */
    toggleRow (rowVm) {
      rowVm.item.selected = !rowVm.item.selected
      this.lastSelectedRow = rowVm
    },

    /**
     * Select all rows between two rows.
     *
     * @param  {VueComponent} firstRowVm  The first row's component
     * @param  {VueComponent} secondRowVm The second row's component
     */
    selectRowsBetween (firstRowVm, secondRowVm) {
      const indexes = [
        this.filteredItems.indexOf(firstRowVm.item),
        this.filteredItems.indexOf(secondRowVm.item)
      ]
      indexes.sort((a, b) => a - b)

      for (let i = indexes[0]; i <= indexes[1]; ++i) {
        this.filteredItems[i].selected = true
      }
    },

    /**
     * Clear the current selection on this song list.
     */
    clearSelection () {
      this.filteredItems.forEach(row => {
        row.selected = false
      })
    },

    /**
     * Enable dragging songs by capturing the dragstart event on a table row.
     * Even though the event is triggered on one row only, we'll collect other
     * selected rows, if any, as well.
     *
     * @param {VueComponent} The row's Vue component
     * @param {Event} event The event
     */
    dragStart (rowVm, event) {
      // If the user is dragging an unselected row, clear the current selection.
      if (!rowVm.item.selected) {
        this.clearSelection()
        rowVm.item.selected = true
      }

      const songIds = this.selectedSongs.map(song => song.id)
      event.dataTransfer.effectAllowed = 'move'
      event.dataTransfer.setData('application/x-koel.text+plain', songIds)

      // Set a fancy drop image using our ghost element.
      const ghost = document.getElementById('dragGhost')
      ghost.innerText = `${pluralize(songIds.length, 'song')}`
      event.dataTransfer.setDragImage(ghost, 0, 0)
    },

    /**
     * Add a "droppable" class and set the drop effect when other songs are dragged over a row.
     *
     * @param {Event} event The dragover event.
     */
    allowDrop (event) {
      if (!this.allowSongReordering) {
        return
      }

      $.addClass(event.target.parentNode, 'droppable')
      event.dataTransfer.dropEffect = 'move'

      return false
    },

    /**
     * Perform reordering songs upon dropping if the current song list is of type Queue.
     *
     * @param  {VueComponent} rowVm The row's Vue Component
     * @param  {Event} event
     */
    handleDrop (rowVm, event) {
      if (
        !this.allowSongReordering ||
        !event.dataTransfer.getData('application/x-koel.text+plain') ||
        !this.selectedSongs.length
      ) {
        return this.removeDroppableState(event)
      }

      queueStore.move(this.selectedSongs, rowVm.song)

      return this.removeDroppableState(event)
    },

    /**
     * Remove the droppable state (and the styles) from a row.
     *
     * @param  {Event} event
     */
    removeDroppableState (event) {
      $.removeClass(event.target.parentNode, 'droppable')
      return false
    },

    /**
     * Open the context menu.
     *
     * @param  {VueComponent} rowVm The right-clicked row's component
     * @param  {Event} event
     */
    openContextMenu (rowVm, event) {
      // If the user is right-clicking an unselected row,
      // clear the current selection and select it instead.
      if (!rowVm.item.selected) {
        this.clearSelection()
        this.toggleRow(rowVm)
      }

      this.$nextTick(() => this.$refs.contextMenu.open(event.pageY, event.pageX))
    },

    /**
     * Extract the search data from a search query.
     * @param {String} q
     * @return { Object } A { keywords, fields } object
     */
    extractSearchData (q) {
      const re = /in:(title|album|artist)/ig
      const fields = []
      const matches = q.match(re)
      let keywords = q
      if (matches) {
        keywords = q.replace(re, '').trim()
        if (keywords) {
          matches.forEach(match => {
            const field = match.split(':')[1].toLowerCase()
            fields.push(field === 'title' ? `song.${field}` : `song.${field}.name`)
          })
        }
      }
      return {
        keywords,
        fields: fields.length ? fields : ['song.title', 'song.album.name', 'song.artist.name']
      }
    }
  },

  mounted () {
    if (this.items) {
      this.render()
    }
  },

  created () {
    event.on({
      /**
       * Listen to 'filter:changed' event to filter the current list.
       */
      'filter:changed': q => {
        this.q = q
      }
    })
  }
}
</script>

<style lang="scss">
@import "~#/partials/_vars.scss";
@import "~#/partials/_mixins.scss";

.song-list-wrap {
  position: relative;
  padding: 8px 24px;

  .song-list-header {
    position: absolute;
    top: 0;
    left: 24px;
    right: 24px;
    padding: 0 24px;
    background: #1b1b1b;
    z-index: 1;
    width: calc(100% - 48px);
  }

  table {
    width: 100%;
    table-layout: fixed;
  }

  tr.droppable {
    border-bottom-width: 3px;
    border-bottom-color: $colorGreen;
  }

  td, th {
    text-align: left;
    padding: 8px;
    vertical-align: middle;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;

    &.time {
      width: 72px;
      text-align: right;
    }

    &.track-number {
      width: 42px;
    }

    &.artist {
      width: 23%;
    }

    &.album {
      width: 27%;
    }

    &.play {
      display: none;

      html.touchevents & {
        display: block;
        position: absolute;
        top: 8px;
        right: 4px;
      }
    }
  }

  th {
    color: $color2ndText;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;

    i {
      color: $colorHighlight;
      font-size: 1.2rem;
    }
  }

  /**
   * Since the Queue screen doesn't allow sorting, we reset the cursor style.
   */
  &.queue th {
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
      left: 24px;
      right: 24px;
    }

    .item {
      margin-bottom: 0;
    }
  }

  @media only screen and (max-width: 768px) {
    table, tbody, tr {
      display: block;
    }

    thead, tfoot {
      display: none;
    }

    .scroller {
      top: 0;
      bottom: 24px;

      .item-container {
        left: 12px;
        right: 12px;
      }
    }

    tr {
      padding: 8px 32px 8px 4px;
      position: relative;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      color: $color2ndText;
      width: 100%;
    }

    td {
      display: inline;
      padding: 0;
      vertical-align: bottom;
      color: $colorMainText;

      &.album, &.time, &.track-number {
        display: none;
      }

      &.artist {
        color: $color2ndText;
        font-size: .9rem;
        padding: 0 4px;
      }
    }
  }
}
</style>
