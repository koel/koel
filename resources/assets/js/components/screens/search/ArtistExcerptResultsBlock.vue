<template>
  <ExcerptResultBlock>
    <template #header>Artists</template>

    <ul v-if="searching" class="results">
      <li v-for="i in 6" :key="i">
        <ArtistCardSkeleton layout="compact" />
      </li>
    </ul>
    <template v-else>
      <ul v-if="artists.length" class="results">
        <li v-for="artist in artists" :key="artist.id">
          <ArtistCard :artist="artist" layout="compact" />
        </li>
      </ul>
      <p v-else>None found.</p>
    </template>
  </ExcerptResultBlock>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

import ArtistCard from '@/components/artist/ArtistCard.vue'
import ArtistCardSkeleton from '@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'
import ExcerptResultBlock from '@/components/screens/search/ExcerptResultBlock.vue'

const props = withDefaults(defineProps<{ artists?: Artist[], searching?: boolean }>(), {
  artists: () => [],
  searching: false,
})

const { artists, searching } = toRefs(props)
</script>

<style lang="postcss" scoped>
.results {
  @apply grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3;
}
</style>
