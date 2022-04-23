<template>
  <section id="searchExcerptsWrapper">
    <ScreenHeader>
      <span v-if="q">Search Results for <strong>{{ q }}</strong></span>
      <span v-else>Search</span>
    </ScreenHeader>

    <div class="main-scroll-wrap" ref="wrapper">
      <div class="results" v-if="q">
        <section class="songs" data-testid="song-excerpts">
          <h1>
            Songs
            <Btn
              v-if="state.excerpt.songs.length"
              data-test="view-all-songs-btn"
              orange
              rounded
              small
              @click.prevent="goToSongResults"
            >
              View All
            </Btn>
          </h1>
          <ul v-if="state.excerpt.songs.length">
            <li is="vue:SongCard" v-for="song in state.excerpt.songs" :key="song.id" :song="song"/>
          </ul>
          <p v-else>None found.</p>
        </section>

        <section class="artists" data-testid="artist-excerpts">
          <h1>Artists</h1>
          <ul v-if="state.excerpt.artists.length">
            <li v-for="artist in state.excerpt.artists" :key="artist.id">
              <ArtistCard :artist="artist" layout="compact"/>
            </li>
          </ul>
          <p v-else>None found.</p>
        </section>

        <section class="albums" data-testid="album-excerpts">
          <h1>Albums</h1>
          <ul v-if="state.excerpt.albums.length">
            <li v-for="album in state.excerpt.albums" :key="album.id">
              <AlbumCard :album="album" layout="compact"/>
            </li>
          </ul>
          <p v-else>None found.</p>
        </section>
      </div>

      <ScreenEmptyState v-else>
        <template v-slot:icon>
          <i class="fa fa-search"></i>
        </template>
        Find songs, artists, and albums,
        <span class="secondary d-block">all in one place.</span>
      </ScreenEmptyState>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, reactive, ref } from 'vue'
import { eventBus } from '@/utils'
import { searchStore } from '@/stores'
import router from '@/router'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const ScreenEmptyState = defineAsyncComponent(() => import('@/components/ui/ScreenEmptyState.vue'))
const SongCard = defineAsyncComponent(() => import('@/components/song/SongCard.vue'))
const ArtistCard = defineAsyncComponent(() => import('@/components/artist/ArtistCard.vue'))
const AlbumCard = defineAsyncComponent(() => import('@/components/album/AlbumCard.vue'))
const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))

const state = reactive(searchStore.state)
const q = ref('')

const goToSongResults = () => router.go(`search/songs/${q.value}`)

eventBus.on('SEARCH_KEYWORDS_CHANGED', (_q: string) => {
  q.value = _q
  searchStore.excerptSearch(q.value)
})
</script>

<style lang="scss" scoped>
.results > section {
  margin-bottom: 3em;
}

h1 {
  font-size: 1.4rem;
  margin: 0 0 1.8rem;
  font-weight: 100;
  display: flex;
  place-content: space-between;
}

section ul {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  grid-gap: .7em 1em;

  @media only screen and (max-width: 667px) {
    display: block;

    > * + * {
      margin-top: .7rem;
    }
  }
}
</style>
