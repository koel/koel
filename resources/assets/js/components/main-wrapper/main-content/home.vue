<template>
  <section id="homeWrapper">
    <h1 class="heading">
      <span>{{ greeting }}</span>
    </h1>

    <div class="main-scroll-wrap" @scroll="scrolling" ref="wrapper">
      <div class="two-cols">
        <section v-show="top.songs.length">
          <h1>Most Played</h1>

          <ol class="top-song-list">
            <li v-for="song in top.songs"
              :top-play-count="top.songs.length ? top.songs[0].playCount : 0"
              :song="song"
              is="song-item"/>
          </ol>
        </section>

        <section class="recent">
          <h1>Recently Played</h1>

          <ol class="recent-song-list" v-show="recentSongs.length">
            <li v-for="song in recentSongs"
              :top-play-count="top.songs.length ? top.songs[0].playCount : 0"
              :song="song"
              is="song-item"/>
          </ol>

          <p class="none" v-show="!recentSongs.length">
            Your most-recent songs in this session will be displayed here.<br />
            Start listening!
          </p>
        </section>
      </div>

      <section class="recently-added" v-show="showRecentlyAddedSection">
        <h1>Recently Added</h1>

        <div class="two-cols">
          <div class="wrapper as-list">
            <album-item v-for="album in recentlyAdded.albums" :album="album"/>
            <span class="item filler" v-for="n in 3"/>
          </div>
          <div>
            <ul class="recently-added-song-list" v-show="recentlyAdded.songs.length">
              <li v-for="song in recentlyAdded.songs" :song="song" is="song-item"/>
            </ul>
          </div>
        </div>
      </section>

      <section class="top-artists" v-show="top.artists.length">
        <h1>Top Artists</h1>

        <div class="wrapper" :class="'as-'+preferences.artistsViewMode">
          <artist-item v-for="artist in top.artists" :artist="artist"/>
          <span class="item filler" v-for="n in 3"/>
        </div>
      </section>

      <section class="top-albums" :class="'as-'+preferences.albumsViewMode" v-show="top.albums.length">
        <h1>Top Albums</h1>

        <div class="wrapper">
          <album-item v-for="album in top.albums" :album="album"/>
          <span class="item filler" v-for="n in 3"/>
        </div>
      </section>

      <to-top-button :showing="showBackToTop"/>
    </div>
  </section>
</template>

<script>
import { sample } from 'lodash'

import { event } from '../../../utils'
import { songStore, albumStore, artistStore, userStore, preferenceStore } from '../../../stores'
import infiniteScroll from '../../../mixins/infinite-scroll'

import albumItem from '../../shared/album-item.vue'
import artistItem from '../../shared/artist-item.vue'
import songItem from '../../shared/home-song-item.vue'

export default {
  components: { albumItem, artistItem, songItem },
  /**
   * Note: We're not really using infinite scrolling here,
   * but only the handy "Back to Top" button.
   */
  mixins: [infiniteScroll],

  data () {
    return {
      greetings: [
        'Oh hai!',
        'Hey, %s!',
        'Howdy, %s!',
        'Yo!',
        'How’s it going, %s?',
        'Sup, %s?',
        'How’s life, %s?',
        'How’s your day, %s?',
        'How have you been, %s?'
      ],
      recentSongs: [],
      top: {
        songs: [],
        albums: [],
        artists: []
      },
      recentlyAdded: {
        albums: [],
        songs: []
      },

      preferences: preferenceStore.state
    }
  },

  computed: {
    greeting () {
      return sample(this.greetings).replace('%s', userStore.current.name)
    },

    showRecentlyAddedSection () {
      return this.recentlyAdded.albums.length || this.recentlyAdded.songs.length
    }
  },

  methods: {
    /**
     * Refresh the dashboard with latest data.
     */
    refreshDashboard () {
      this.top.songs = songStore.getMostPlayed(7)
      this.top.albums = albumStore.getMostPlayed(6)
      this.top.artists = artistStore.getMostPlayed(6)
      this.recentlyAdded.albums = albumStore.getRecentlyAdded(6)
      this.recentlyAdded.songs = songStore.getRecentlyAdded(10)
      this.recentSongs = songStore.recentlyPlayed
    }
  },

  created () {
    event.on({
      'koel:ready': () => this.refreshDashboard(),

      'song:played': () => this.refreshDashboard()
    })
  }
}
</script>

<style lang="sass">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#homeWrapper {
  .two-cols {
    display: flex;

    > section, > div {
      flex-grow: 1;
      flex-basis: 0;

      &:first-of-type {
        margin-right: 8px;
      }
    }
  }

  .none {
    color: $color2ndText;
    padding: 0;

    a {
      color: $colorHighlight;
    }
  }

  .recently-added {
    .song-item-home .details {
      background: rgba(255, 255, 255, .02);
    }

    .item {
      margin-bottom: 8px;
    }
  }

  .top-artists .wrapper, .top-albums .wrapper, .recently-added .wrapper {
    @include artist-album-wrapper();
  }

  .main-scroll-wrap {
    section {
      margin-bottom: 48px;
    }

    h1 {
      font-size: 1.4rem;
      margin: 0 0 1.8rem;
      font-weight: $fontWeight_UltraThin;
    }
  }

  @media only screen and (max-width: 768px) {
    .two-cols {
      display: block;

      > section, > div {
        &:first-of-type {
          margin-right: 0;
        }
      }
    }
  }
}
</style>
