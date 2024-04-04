<template>
  <ExcerptResultBlock>
    <template #header>
      Songs

      <Btn
        v-if="songs.length && !searching"
        data-testid="view-all-songs-btn"
        highlight
        rounded
        small
        @click.prevent="goToSongResults"
      >
        View All
      </Btn>
    </template>

    <ul v-if="searching" class="results">
      <li v-for="i in 6" :key="i">
        <SongCardSkeleton />
      </li>
    </ul>
    <template v-else>
      <ul v-if="songs.length" class="results">
        <li v-for="song in songs" :key="song.id">
          <SongCard :song="song" />
        </li>
      </ul>
      <p v-else>None found.</p>
    </template>
  </ExcerptResultBlock>
</template>

<script setup lang="ts">
import { toRefs } from 'vue'
import { useRouter } from '@/composables'

import SongCardSkeleton from '@/components/ui/skeletons/SongCardSkeleton.vue'
import ExcerptResultBlock from '@/components/screens/search/ExcerptResultBlock.vue'
import SongCard from '@/components/song/SongCard.vue'
import Btn from '@/components/ui/form/Btn.vue'

const props = withDefaults(defineProps<{ songs?: Song[], query?: string, searching?: boolean }>(), {
  songs: () => [],
  query: '',
  searching: false,
})

const { songs, query, searching } = toRefs(props)
const { go } = useRouter()

const goToSongResults = () => go(`search/songs/?q=${query.value}`)
</script>

<style scoped lang="postcss">
.results {
  @apply grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3;
}
</style>
