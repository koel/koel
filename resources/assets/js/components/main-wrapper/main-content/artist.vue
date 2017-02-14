<template>
  <section id="artistWrapper">
    <h1 class="heading">
      <span class="overview">
        <img :src="artist.image" width="64" height="64" class="cover">
        {{ artist.name }}
        <controls-toggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

        <span class="meta" v-show="meta.songCount">
          {{ artist.albums.length | pluralize('album') }}
          •
          {{ meta.songCount | pluralize('song') }}
          •
          {{ meta.totalLength }}

          <template v-if="sharedState.useLastfm">
            •
            <a class="info" href @click.prevent="showInfo" title="View artist's extra information">Info</a>
          </template>

          <template v-if="sharedState.allowDownload">
            •
            <a class="download" href @click.prevent="download" title="Download all songs by this artist">
              Download All
            </a>
          </template>
        </span>
      </span>

      <song-list-controls
        v-show="artist.songs.length && (!isPhone || showingControls)"
        @shuffleAll="shuffleAll"
        @shuffleSelected="shuffleSelected"
        :config="songListControlConfig"
        :selectedSongs="selectedSongs"
      />
    </h1>

    <song-list :items="artist.songs" type="artist" ref="songList"/>

    <section class="info-wrapper" v-if="sharedState.useLastfm && info.showing">
      <a href class="close" @click.prevent="info.showing = false"><i class="fa fa-times"></i></a>
      <div class="inner">
        <div class="loading" v-if="info.loading"><sound-bar/></div>
        <artist-info :artist="artist" :mode="'full'" v-else/>
      </div>
    </section>
  </section>
</template>

<script>
import { pluralize, event } from '../../../utils'
import { sharedStore, artistStore } from '../../../stores'
import { playback, download, artistInfo as artistInfoService } from '../../../services'
import router from '../../../router'
import hasSongList from '../../../mixins/has-song-list'
import artistInfo from '../extra/artist-info.vue'
import soundBar from '../../shared/sound-bar.vue'

export default {
  name: 'main-wrapper--main-content--artist',
  mixins: [hasSongList],
  components: { artistInfo, soundBar },
  filters: { pluralize },

  data () {
    return {
      sharedState: sharedStore.state,
      artist: artistStore.stub,
      info: {
        showing: false,
        loading: true
      }
    }
  },

  watch: {
    /**
     * Watch the artist's album count.
     * If this is changed to 0, the user has edit the songs by this artist
     * and move all of them to another artist (thus delete this artist entirely).
     * We should then go back to the artist list.
     */
    'artist.albums.length' (newVal) {
      if (!newVal) {
        router.go('artists')
      }
    }
  },

  created () {
    /**
     * Listen to 'main-content-view:load' event to load the requested artist
     * into view if applicable.
     *
     * @param {String} view   The view's name
     * @param {Object} artist
     */
    event.on('main-content-view:load', (view, artist) => {
      if (view === 'artist') {
        this.info.showing = false
        this.artist = artist
        // #530
        this.$nextTick(() => {
          this.$refs.songList.sort()
        })
      }
    })
  },

  methods: {
    /**
     * Shuffle the songs by the current artist.
     * Overriding the mixin.
     */
    shuffleAll () {
      playback.queueAndPlay(this.artist.songs, true)
    },

    /**
     * Download all songs by the artist.
     */
    download () {
      download.fromArtist(this.artist)
    },

    showInfo () {
      this.info.showing = true
      if (!this.artist.info) {
        this.info.loading = true
        artistInfoService.fetch(this.artist).then(() => {
          this.info.loading = false
        })
      } else {
        this.info.loading = false
      }
    }
  }
}
</script>

<style lang="scss" scoped>
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#artistWrapper {
  button.play-shuffle {
    i {
      margin-right: 0 !important;
    }
  }

  .heading {
    .overview {
      position: relative;
      padding-left: 84px;

      @media only screen and (max-width : 768px) {
        padding-left: 0;
      }
    }

    .cover {
      position: absolute;
      left: 0;
      top: -7px;

      @media only screen and (max-width : 768px) {
        display: none;
      }
    }
  }

  @include artist-album-info-wrapper();
}
</style>
