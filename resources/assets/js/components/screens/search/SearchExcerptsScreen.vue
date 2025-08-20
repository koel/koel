<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">
        <span v-if="q">Searching for <span class="font-thin">{{ q }}</span></span>
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
      <PodcastExcerptResultsBlock :podcasts="excerpt.podcasts" :searching data-testid="podcast-excerpts" />
      <RadioStationExcerptResultsBlock
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
import { intersectionBy } from 'lodash'
import { ref, toRef } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { searchStore } from '@/stores/searchStore'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import PlayableExcerptResultsBlock from '@/components/screens/search/PlayableExcerptResultsBlock.vue'
import ArtistResultsBlock from '@/components/screens/search/ArtistExcerptResultsBlock.vue'
import AlbumResultsBlock from '@/components/screens/search/AlbumExcerptResultsBlock.vue'
import PodcastExcerptResultsBlock from '@/components/screens/search/PodcastExcerptResultsBlock.vue'
import RadioStationExcerptResultsBlock from '@/components/screens/search/RadioStationExcerptResultsBlock.vue'

const excerpt = toRef(searchStore.state, 'excerpt')
const q = ref('')
const searching = ref(false)

const doSearch = async () => {
  searching.value = true
  await searchStore.excerptSearch(q.value)
  searching.value = false
}

eventBus.on('SEARCH_KEYWORDS_CHANGED', async _q => {
  q.value = _q
  await doSearch()
}).on('SONGS_DELETED', async songs => {
  if (intersectionBy(songs, excerpt.value.playables, 'id').length !== 0) {
    await doSearch()
  }
})
</script>
