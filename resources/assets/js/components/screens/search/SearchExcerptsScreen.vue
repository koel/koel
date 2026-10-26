<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">
        <span v-if="q"
          >Searching for <span class="font-thin">{{ q }}</span></span
        >
        <span v-else>Search</span>
      </ScreenHeader>
    </template>

    <div v-if="q" class="space-y-8">
      <PlayableExcerptResultsBlock
        :playables="excerpt.playables"
        :query="q"
        :searching
        data-testid="playable-excerpts"
      />
      <ArtistResultsBlock :artists="excerpt.artists" :searching data-testid="artist-excerpts" />
      <AlbumResultsBlock :albums="excerpt.albums" :searching data-testid="album-excerpts" />
      <PodcastExcerptResultsBlock
        v-if="usesPodcasts"
        :podcasts="excerpt.podcasts"
        :searching
        data-testid="podcast-excerpts"
      />
      <RadioStationExcerptResultsBlock
        v-if="usesRadio"
        :stations="excerpt.radio_stations"
        :searching
        data-testid="radio-station-excerpts"
      />
    </div>

    <ScreenEmptyState v-else>
      <template #icon>
        <Icon :icon="faSearch" />
      </template>
      Find songs, artists, and albums,
      <span class="secondary block">all in one place.</span>
    </ScreenEmptyState>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faSearch } from '@fortawesome/free-solid-svg-icons'
import { intersectionBy } from 'lodash-es'
import { ref, toRef } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { searchStore } from '@/stores/searchStore'
import { commonStore } from '@/stores/commonStore'
import { usePageTitle } from '@/composables/usePageTitle'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import PlayableExcerptResultsBlock from '@/components/screens/search/PlayableExcerptResultsBlock.vue'
import ArtistResultsBlock from '@/components/screens/search/ArtistExcerptResultsBlock.vue'
import AlbumResultsBlock from '@/components/screens/search/AlbumExcerptResultsBlock.vue'
import PodcastExcerptResultsBlock from '@/components/screens/search/PodcastExcerptResultsBlock.vue'
import RadioStationExcerptResultsBlock from '@/components/screens/search/RadioStationExcerptResultsBlock.vue'

const excerpt = toRef(searchStore.state, 'excerpt')
const usesPodcasts = toRef(commonStore.state, 'uses_podcasts')
const usesRadio = toRef(commonStore.state, 'uses_radio')
const q = ref('')
const searching = ref(false)

usePageTitle().useScreenTitle('Search.Excerpt', () => q.value)

let latestSearchId = 0

const doSearch = async () => {
  const searchId = ++latestSearchId

  searching.value = true
  await searchStore.excerptSearch(q.value)

  if (searchId === latestSearchId) {
    searching.value = false
  }
}

const searchKeywords = async (keywords: string) => {
  q.value = keywords
  await doSearch()
}

if (searchStore.state.keywords) {
  searchKeywords(searchStore.state.keywords)
}

eventBus.on('SEARCH_KEYWORDS_CHANGED', searchKeywords).on('SONGS_DELETED', async songs => {
  if (intersectionBy(songs, excerpt.value.playables, 'id').length !== 0) {
    await doSearch()
  }
})
</script>
