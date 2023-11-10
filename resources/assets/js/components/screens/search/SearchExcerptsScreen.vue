<template>
  <section id="searchExcerptsWrapper">
    <ScreenHeader layout="collapsed">
      <span v-if="q">Searching for <span class="text-thin">{{ q }}</span></span>
      <span v-else>Search</span>
    </ScreenHeader>

    <div ref="wrapper" class="main-scroll-wrap">
      <div v-if="q" class="results">
        <section class="songs" data-testid="song-excerpts">
          <h1>
            Songs
            <Btn
              v-if="excerpt.songs.length && !searching"
              data-testid="view-all-songs-btn"
              orange
              rounded
              small
              @click.prevent="goToSongResults"
            >
              View All
            </Btn>
          </h1>
          <ul v-if="searching">
            <li v-for="i in 6" :key="i">
              <SongCardSkeleton />
            </li>
          </ul>
          <template v-else>
            <ul v-if="excerpt.songs.length">
              <li v-for="song in excerpt.songs" :key="song.id">
                <SongCard :song="song" />
              </li>
            </ul>
            <p v-else>None found.</p>
          </template>
        </section>

        <section class="artists" data-testid="artist-excerpts">
          <h1>Artists</h1>
          <ul v-if="searching">
            <li v-for="i in 6" :key="i">
              <ArtistAlbumCardSkeleton layout="compact" />
            </li>
          </ul>
          <template v-else>
            <ul v-if="excerpt.artists.length">
              <li v-for="artist in excerpt.artists" :key="artist.id">
                <ArtistCard :artist="artist" layout="compact" />
              </li>
            </ul>
            <p v-else>None found.</p>
          </template>
        </section>

        <section class="albums" data-testid="album-excerpts">
          <h1>Albums</h1>
          <ul v-if="searching">
            <li v-for="i in 6" :key="i">
              <ArtistAlbumCardSkeleton layout="compact" />
            </li>
          </ul>
          <template v-else>
            <ul v-if="excerpt.albums.length">
              <li v-for="album in excerpt.albums" :key="album.id">
                <AlbumCard :album="album" layout="compact" />
              </li>
            </ul>
            <p v-else>None found.</p>
          </template>
        </section>
      </div>

      <ScreenEmptyState v-else>
        <template #icon>
          <Icon :icon="faSearch" />
        </template>
        Find songs, artists, and albums,
        <span class="secondary d-block">all in one place.</span>
      </ScreenEmptyState>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { faSearch } from '@fortawesome/free-solid-svg-icons'
import { intersectionBy } from 'lodash'
import { ref, toRef } from 'vue'
import { eventBus } from '@/utils'
import { searchStore } from '@/stores'
import { useRouter } from '@/composables'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ArtistCard from '@/components/artist/ArtistCard.vue'
import AlbumCard from '@/components/album/AlbumCard.vue'
import Btn from '@/components/ui/Btn.vue'
import SongCard from '@/components/song/SongCard.vue'
import SongCardSkeleton from '@/components/ui/skeletons/SongCardSkeleton.vue'
import ArtistAlbumCardSkeleton from '@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'

const { go } = useRouter()

const excerpt = toRef(searchStore.state, 'excerpt')
const q = ref('')
const searching = ref(false)

const goToSongResults = () => go(`search/songs/?q=${q.value}`)

const doSearch = async () => {
  searching.value = true
  await searchStore.excerptSearch(q.value)
  searching.value = false
}

eventBus.on('SEARCH_KEYWORDS_CHANGED', async _q => {
  q.value = _q
  await doSearch()
}).on('SONGS_DELETED', async songs => {
  if (intersectionBy(songs, excerpt.value.songs, 'id').length !== 0) {
    await doSearch()
  }
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
