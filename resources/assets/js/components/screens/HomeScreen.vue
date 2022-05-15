<template>
  <section id="homeWrapper">
    <ScreenHeader>{{ greeting }}</ScreenHeader>

    <div class="main-scroll-wrap" @scroll="scrolling">
      <div class="two-cols">
        <section v-if="top.songs.length">
          <h1>Most Played</h1>

          <ol class="top-song-list">
            <li v-for="song in top.songs" :key="song.id">
              <SongCard :song="song" :top-play-count="top.songs.length ? top.songs[0].playCount : 0"/>
            </li>
          </ol>
        </section>

        <section class="recent">
          <h1>
            Recently Played
            <Btn
              data-testid="home-view-all-recently-played-btn"
              @click.prevent="goToRecentlyPlayedScreen"
              rounded
              small
              orange
            >
              View All
            </Btn>
          </h1>

          <ol v-if="recentSongs.length" class="recent-song-list">
            <li v-for="song in recentSongs" :key="song.id">
              <SongCard :song="song" :top-play-count="top.songs.length ? top.songs[0].playCount : 0"/>
            </li>
          </ol>

          <p v-show="!recentSongs.length" class="text-secondary">
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
              <AlbumCard :album="album" layout="compact"/>
            </li>
          </ol>
          <ol v-show="recentlyAdded.songs.length" class="recently-added-song-list">
            <li v-for="song in recentlyAdded.songs" :key="song.id">
              <SongCard :song="song"/>
            </li>
          </ol>
        </div>
      </section>

      <section v-if="top.artists.length">
        <h1>Top Artists</h1>
        <ol class="two-cols top-artist-list">
          <li v-for="artist in top.artists" :key="artist.id">
            <ArtistCard :artist="artist" layout="compact"/>
          </li>
        </ol>
      </section>

      <section v-if="top.albums.length">
        <h1>Top Albums</h1>
        <ol class="two-cols top-album-list">
          <li v-for="album in top.albums" :key="album.id">
            <AlbumCard :album="album" layout="compact"/>
          </li>
        </ol>
      </section>

      <ToTopButton/>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { sample } from 'lodash'
import { computed, defineAsyncComponent, reactive, ref } from 'vue'

import { eventBus } from '@/utils'
import { albumStore, artistStore, recentlyPlayedStore, songStore, userStore } from '@/stores'
import { useInfiniteScroll } from '@/composables'
import router from '@/router'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const AlbumCard = defineAsyncComponent(() => import('@/components/album/AlbumCard.vue'))
const ArtistCard = defineAsyncComponent(() => import('@/components/artist/ArtistCard.vue'))
const SongCard = defineAsyncComponent(() => import('@/components/song/SongCard.vue'))
const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))

const { ToTopButton, scrolling } = useInfiniteScroll()

const greetings = [
  'Oh hai!',
  'Hey, %s!',
  'Howdy, %s!',
  'Yo!',
  'How’s it going, %s?',
  'Sup, %s?',
  'How’s life, %s?',
  'How’s your day, %s?',
  'How have you been, %s?'
]

const greeting = ref('')
const recentSongs = ref<Song[]>([])

const top = reactive({
  songs: [] as Song[],
  albums: [] as Album[],
  artists: [] as Artist[]
})

const recentlyAdded = reactive({
  albums: [] as Album[],
  songs: [] as Song[]
})

const showRecentlyAddedSection = computed(() => Boolean(recentlyAdded.albums.length || recentlyAdded.songs.length))

const refreshDashboard = () => {
  top.songs = songStore.getMostPlayed(7)
  top.albums = albumStore.getMostPlayed(6)
  top.artists = artistStore.getMostPlayed(6)
  recentlyAdded.albums = albumStore.getRecentlyAdded(6)
  recentlyAdded.songs = songStore.getRecentlyAdded(10)
  recentSongs.value = recentlyPlayedStore.excerptState.songs
}

const goToRecentlyPlayedScreen = () => router.go('recently-played')

eventBus.on(['KOEL_READY', 'SONG_STARTED', 'SONG_UPLOADED'], () => refreshDashboard())
eventBus.on('KOEL_READY', () => (greeting.value = sample(greetings)!.replace('%s', userStore.current.name)))
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
