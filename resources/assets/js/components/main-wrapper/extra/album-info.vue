<template>
  <article id="albumInfo" :class="mode">
    <h1 class="name">
      <span>{{ album.name }}</span>

      <a class="shuffle" @click.prevent="shuffleAll"><i class="fa fa-random"></i></a>
    </h1>

    <div v-if="album.info">
      <img v-if="album.info.image" :src="album.info.image" class="cover">

      <div class="wiki" v-if="album.info.wiki && album.info.wiki.summary">
        <div class="summary" v-show="showSummary" v-html="album.info.wiki.summary"/>
        <div class="full" v-show="showFull" v-html="album.info.wiki.full"/>

        <button class="more" v-if="showSummary" @click.prevent="showingFullWiki = true">
          Full Wiki
        </button>
      </div>

      <section class="track-listing" v-if="album.info.tracks.length">
        <h1>Track Listing</h1>
        <ul class="tracks">
          <li is="track-list-item"
            v-for="(track, idx) in album.info.tracks"
            :album="album"
            :track="track"
            :index="idx"
          />
        </ul>
      </section>

      <footer>Data &copy; <a target="_blank" :href="album.info.url">Last.fm</a></footer>
    </div>

    <p class="none" v-else>No album information found.</p>
  </article>
</template>

<script>
import { sharedStore } from '@/stores'
import { playback, ls } from '@/services'
import trackListItem from '@/components/shared/track-list-item.vue'

export default {
  props: {
    album: Object,
    mode: {
      type: String,
      default: 'sidebar',
      validator: value => ['sidebar', 'full'].indexOf(value) !== -1
    }
  },
  components: { trackListItem },

  data () {
    return {
      showingFullWiki: false,
      useiTunes: sharedStore.state.useiTunes
    }
  },

  watch: {
    /**
     * Whenever a new album is loaded into this component, we reset the "full wiki" state.
     * @return {Boolean}
     */
    album () {
      this.showingFullWiki = false
    }
  },

  computed: {
    showSummary () {
      return this.mode !== 'full' && !this.showingFullWiki
    },

    showFull () {
      return this.mode === 'full' || this.showingFullWiki
    },

    iTunesUrl () {
      return `${window.BASE_URL}api/itunes/album/${this.album.id}&jwt-token=${ls.get('jwt-token')}`
    }
  },

  methods: {
    /**
     * Shuffle all songs in the current album.
     */
    shuffleAll () {
      playback.playAllInAlbum(this.album)
    }
  }
}
</script>

<style lang="scss">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#albumInfo {
  @include artist-album-info();
}
</style>
