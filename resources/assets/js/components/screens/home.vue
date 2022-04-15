<template>
  <section id="homeWrapper">
    <screen-header>{{ greeting }}</screen-header>

    <div class="main-scroll-wrap" @scroll="scrolling" ref="wrapper">
      <div class="two-cols">
        <section v-if="top.songs.length">
          <h1>Most Played</h1>

          <ol class="top-song-list">
            <li v-for="song in top.songs" :key="song.id">
              <song-card :song="song" :top-play-count="top.songs.length ? top.songs[0].playCount : 0"/>
            </li>
          </ol>
        </section>

        <section class="recent">
          <h1>
            Recently Played
            <btn
              data-testid="home-view-all-recently-played-btn"
              @click.prevent="goToRecentlyPlayedScreen"
              rounded
              small
              orange
            >
              View All
            </btn>
          </h1>

          <ol class="recent-song-list" v-if="recentSongs.length">
            <li v-for="song in recentSongs" :key="song.id">
              <song-card :song="song" :top-play-count="top.songs.length ? top.songs[0].playCount : 0"/>
            </li>
          </ol>

          <p class="text-secondary" v-show="!recentSongs.length">
            Your recently played songs will be displayed here.<br/>
            Start listening!
          </p>
        </section>
      </div>

      <section v-if="showRecentlyAddedSection">
        <h1>Recently Added</h1>
        <div class="two-cols">
          <ol class="recently-added-album-list">
            <li v-for="album in recentlyAdded.albums" :key="album.id">
              <album-card :album="album" layout="compact"/>
            </li>
          </ol>
          <ol class="recently-added-song-list" v-show="recentlyAdded.songs.length">
            <li v-for="song in recentlyAdded.songs" :key="song.id">
              <song-card :song="song"/>
            </li>
          </ol>
        </div>
      </section>

      <section v-if="top.artists.length">
        <h1>Top Artists</h1>
        <ol class="two-cols top-artist-list">
          <li v-for="artist in top.artists" :key="artist.id">
            <artist-card :artist="artist" layout="compact"/>
          </li>
        </ol>
      </section>

      <section v-if="top.albums.length">
        <h1>Top Albums</h1>
        <ol class="two-cols top-album-list">
          <li v-for="album in top.albums" :key="album.id">
            <album-card :album="album" layout="compact"/>
          </li>
        </ol>
      </section>

      <to-top-button/>
    </div>
  </section>
</template>

<script lang="ts">
import { sample } from 'lodash'
import mixins from 'vue-typed-mixins'

import { eventBus } from '@/utils'
import { songStore, albumStore, artistStore, recentlyPlayedStore, userStore, preferenceStore } from '@/stores'
import infiniteScroll from '@/mixins/infinite-scroll.ts'
import router from '@/router'

export default mixins(infiniteScroll).extend({
  components: {
    ScreenHeader: () => import('@/components/ui/screen-header.vue'),
    AlbumCard: () => import('@/components/album/card.vue'),
    ArtistCard: () => import('@/components/artist/card.vue'),
    SongCard: () => import('@/components/song/card.vue'),
    Btn: () => import('@/components/ui/btn.vue')
  },

  data: () => ({
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
    recentSongs: [] as Song[],
    top: {
      songs: [] as Song[],
      albums: [] as Album[],
      artists: [] as Artist[]
    },
    recentlyAdded: {
      albums: [] as Album[],
      songs: [] as Song[]
    },

    preferences: preferenceStore.state
  }),

  computed: {
    greeting (): string {
      return sample(this.greetings)!.replace('%s', userStore.current.name)
    },

    showRecentlyAddedSection (): boolean {
      return Boolean(this.recentlyAdded.albums.length || this.recentlyAdded.songs.length)
    }
  },

  methods: {
    refreshDashboard (): void {
      this.top.songs = songStore.getMostPlayed(7)
      this.top.albums = albumStore.getMostPlayed(6)
      this.top.artists = artistStore.getMostPlayed(6)
      this.recentlyAdded.albums = albumStore.getRecentlyAdded(6)
      this.recentlyAdded.songs = songStore.getRecentlyAdded(10)
      this.recentSongs = recentlyPlayedStore.excerptState.songs
    },

    goToRecentlyPlayedScreen: (): void => router.go('recently-played')
  },

  created (): void {
    eventBus.on({
      'KOEL_READY': (): void => this.refreshDashboard(),
      'SONG_STARTED': (): void => this.refreshDashboard(),
      'SONG_UPLOADED': (): void => this.refreshDashboard()
    })
  }
})
</script>

<style lang="scss">
#homeWrapper {
  .two-cols {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-gap: .7em 1em;

    ol, li {
      overflow: hidden;
    }
  }

  .recent {
    h1 button {
      float: right;
      padding: 6px 10px;
      margin-top: -3px;
    }
  }

  ol {
    display: grid;
    grid-gap: .7em 1em;
    align-content: start;
  }

  .main-scroll-wrap {
    section {
      margin-bottom: 48px;
    }

    h1 {
      font-size: 1.4rem;
      margin: 0 0 1.8rem;
      font-weight: var(--font-weight-thin);
    }
  }

  @media only screen and (max-width: 768px) {
    .two-cols {
      grid-template-columns: 1fr;
    }
  }
}
</style>
