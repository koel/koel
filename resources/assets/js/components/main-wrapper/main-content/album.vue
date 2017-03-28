<template>
  <section id="albumWrapper">
    <h1 class="heading">
      <span class="overview">
        <img :src="album.cover" width="64" height="64" class="cover">
        {{ album.name }}
        <controls-toggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

        <span class="meta" v-show="meta.songCount">
          by
          <a class="artist" v-if="isNormalArtist" :href="`/#!/artist/${album.artist.id}`">{{ album.artist.name }}</a>
          <span class="nope" v-else>{{ album.artist.name }}</span>
          •
          {{ meta.songCount | pluralize('song') }}
          •
          {{ meta.totalLength }}

          <template v-if="sharedState.useLastfm">
            •
            <a class="info" href @click.prevent="showInfo" title="View album's extra information">Info</a>
          </template>
          <template v-if="sharedState.allowDownload">
            •
            <a class="download" href @click.prevent="download" title="Download all songs in album">
              Download All
            </a>
          </template>
        </span>
      </span>

      <song-list-controls
        v-show="album.songs.length && (!isPhone || showingControls)"
        @shuffleAll="shuffleAll"
        @shuffleSelected="shuffleSelected"
        :config="songListControlConfig"
        :selectedSongs="selectedSongs"
      />
    </h1>

    <song-list :items="album.songs" type="album" ref="songList"/>

    <section class="info-wrapper" v-if="sharedState.useLastfm && info.showing">
      <a href class="close" @click.prevent="info.showing = false"><i class="fa fa-times"></i></a>
      <div class="inner">
        <div class="loading" v-if="info.loading"><sound-bar/></div>
        <album-info :album="album" mode="full" v-else/>
      </div>
    </section>
  </section>
</template>

<script>
import { pluralize, event } from '../../../utils'
import { albumStore, artistStore, sharedStore } from '../../../stores'
import { playback, download, albumInfo as albumInfoService } from '../../../services'
import router from '../../../router'
import hasSongList from '../../../mixins/has-song-list'
import albumInfo from '../extra/album-info.vue'
import soundBar from '../../shared/sound-bar.vue'

export default {
  name: 'main-wrapper--main-content--album',
  mixins: [hasSongList],
  components: { albumInfo, soundBar },
  filters: { pluralize },

  data () {
    return {
      sharedState: sharedStore.state,
      album: albumStore.stub,
      info: {
        showing: false,
        loading: true
      }
    }
  },

  computed: {
    isNormalArtist () {
      return !artistStore.isVariousArtists(this.album.artist) &&
        !artistStore.isUnknownArtist(this.album.artist)
    }
  },

  watch: {
    /**
     * Watch the album's song count.
     * If this is changed to 0, the user has edit the songs in this album
     * and move all of them into another album.
     * We should then go back to the album list.
     */
    'album.songs.length' (newVal) {
      if (!newVal) {
        router.go('albums')
      }
    }
  },

  created () {
    /**
     * Listen to 'main-content-view:load' event to load the requested album
     * into view if applicable.
     *
     * @param {String} view   The view name
     * @param {Object} album  The album object
     */
    event.on('main-content-view:load', (view, album) => {
      if (view === 'album') {
        this.info.showing = false
        this.album = album
        // #530
        this.$nextTick(() => {
          this.$refs.songList.sort()
        })
      }
    })
  },

  methods: {
    /**
     * Shuffle the songs in the current album.
     * Overriding the mixin.
     */
    shuffleAll () {
      playback.queueAndPlay(this.album.songs, true)
    },

    /**
     * Download all songs from the album.
     */
    download () {
      download.fromAlbum(this.album)
    },

    showInfo () {
      this.info.showing = true
      if (!this.album.info) {
        this.info.loading = true
        albumInfoService.fetch(this.album).then(() => {
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

#albumWrapper {
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

    a.artist {
      color: $colorMainText;
      display: inline;

      &:hover {
        color: $colorHighlight;
      }
    }
  }

  @include artist-album-info-wrapper();
}
</style>
