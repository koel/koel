<template>
  <article class="item" v-if="showing" draggable="true" @dragstart="dragStart">
    <span class="cover" :style="{ backgroundImage: 'url('+artist.image+')' }">
      <a class="control" @click.prevent="play">
        <i class="fa fa-play"></i>
      </a>
    </span>
    <footer>
      <div class="info">
        <a class="name" :href="'/#!/artist/'+artist.id">{{ artist.name }}</a>
      </div>
      <p class="meta">
        <span class="left">
          {{ artist.albums.length | pluralize('album') }}
          •
          {{ artist.songCount | pluralize('song') }}
          •
          {{ artist.playCount | pluralize('play') }}
        </span>
        <span class="right">
          <a href @click.prevent="download" v-if="sharedState.allowDownload" title="Download all songs by artist">
            <i class="fa fa-download"></i>
          </a>
        </span>
      </p>
    </footer>
  </article>
</template>

<script>
import { pluralize } from '../../utils'
import { artistStore, queueStore, sharedStore } from '../../stores'
import { playback, download } from '../../services'

export default {
  name: 'shared--artist-item',
  props: ['artist'],
  filters: { pluralize },

  data () {
    return {
      sharedState: sharedStore.state
    }
  },

  computed: {
    /**
     * Determine if the artist item should be shown.
     * We're not showing those without any songs, or the special "Various Artists".
     *
     * @return {Boolean}
     */
    showing () {
      return this.artist.songCount && !artistStore.isVariousArtists(this.artist)
    }
  },

  methods: {
    /**
     * Play all songs by the current artist, or queue them up if Ctrl/Cmd key is pressed.
     */
    play (e) {
      if (e.metaKey || e.ctrlKey) {
        queueStore.queue(this.artist.songs)
      } else {
        playback.playAllByArtist(this.artist, false)
      }
    },

    /**
     * Download all songs by artist.
     */
    download () {
      download.fromArtist(this.artist)
    },

    /**
     * Allow dragging the artist (actually, their songs).
     */
    dragStart (e) {
      const songIds = this.artist.songs.map(song => song.id)
      e.dataTransfer.setData('application/x-koel.text+plain', songIds)
      e.dataTransfer.effectAllowed = 'move'

      // Set a fancy drop image using our ghost element.
      const ghost = document.getElementById('dragGhost')
      ghost.innerText = `All ${pluralize(songIds.length, 'song')} by ${this.artist.name}`
      e.dataTransfer.setDragImage(ghost, 0, 0)
    }
  }
}
</script>

<style lang="scss">
@import "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";

@include artist-album-card();
</style>
