<template>
  <tr
    class="song-item"
    draggable="true"
    :data-song-id="song.id"
    @click="clicked"
    @dblclick.prevent="playRightAwayyyyyyy"
    @dragstart="dragStart"
    @dragleave="dragLeave"
    @dragenter.prevent="dragEnter"
    @dragover.prevent
    @drop.stop.prevent="drop"
    @contextmenu.prevent="contextMenu"
    :class="{ selected: item.selected, playing: playing }"
  >
    <td class="track-number">{{ song.track || '' }}</td>
    <td class="title">{{ song.title }}</td>
    <td class="artist">{{ song.artist.name }}</td>
    <td class="album">{{ song.album.name }}</td>
    <td class="time">{{ song.fmtLength }}</td>
    <td class="play" @click.stop="doPlayback">
      <i class="fa fa-pause-circle" v-if="song.playbackState === 'playing'"/>
      <i class="fa fa-play-circle" v-else/>
    </td>
  </tr>
</template>

<script>
import { playback } from '../../services'
import { queueStore } from '../../stores'
import $v from 'vuequery'

export default {
  name: 'song-item',
  props: {
    item: {
      type: Object,
      required: true
    }
  },

  data () {
    return {
      parentSongList: null
    }
  },

  computed: {
    /**
     * A shortcut to access the current vm's song (instead of this.item.song).
     * @return {Object}
     */
    song () {
      return this.item.song
    },

    /**
     * Determine if the current song is being played (or paused).
     * @return {Boolean}
     */
    playing () {
      return this.song.playbackState === 'playing' || this.song.playbackState === 'paused'
    }
  },

  created () {
    this.parentSongList = $v(this).closest('song-list').vm
  },

  methods: {
    /**
     * Play the song right away.
     */
    playRightAwayyyyyyy () {
      queueStore.contains(this.song) || queueStore.queueAfterCurrent(this.song)
      playback.play(this.song)
    },

    /**
     * Take the right playback action based on the current playback state.
     */
    doPlayback () {
      switch (this.song.playbackState) {
        case 'playing':
          playback.pause()
          break
        case 'paused':
          playback.resume()
          break
        default:
          this.playRightAwayyyyyyy()
          break
      }
    },

    /**
     * Proxy the click event to the parent song list component.
     * @param  {Event} event
     */
    clicked (event) {
      this.parentSongList.rowClicked(this, event)
    },

    /**
     * Proxy the dragstart event to the parent song list component.
     * @param  {Event} event
     */
    dragStart (event) {
      this.parentSongList.dragStart(this, event)
    },

    /**
     * Proxy the dragleave event to the parent song list component.
     * @param  {Event} event
     */
    dragLeave (event) {
      this.parentSongList.removeDroppableState(event)
    },

    /**
     * Proxy the dragover event to the parent song list component.
     * @param {Event} event The dragover event.
     */
    dragEnter (event) {
      this.parentSongList.allowDrop(event)
    },

    /**
     * Proxy the dropstop event to the parent song list component.
     * @param  {Event} event
     */
    drop (event) {
      this.parentSongList.handleDrop(this, event)
    },

    /**
     * Proxy the contextmenu event to the parent song list component.
     * @param  {Event} event
     */
    contextMenu (event) {
      this.parentSongList.openContextMenu(this, event)
    }
  }
}
</script>

<style lang="scss">
@import "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";

.song-item {
  border-bottom: 1px solid $color2ndBgr;
  height: 35px;

  html.no-touchevents &:hover {
    background: rgba(255, 255, 255, .05);
  }

  .time, .track-number {
    color: $color2ndText;
  }

  .title {
    min-width: 192px;
  }

  .play {
    max-width: 32px;
    opacity: .5;

    i {
      font-size: 1.5rem;
    }
  }

  &.selected {
    background-color: rgba(255, 255, 255, .08);
  }

  &.playing td {
    color: $colorHighlight;
  }
}
</style>
