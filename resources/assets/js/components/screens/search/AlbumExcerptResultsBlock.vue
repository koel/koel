<template>
  <ExcerptResultBlock>
    <template #header>Albums</template>

    <ul v-if="searching" class="results">
      <li v-for="i in 6" :key="i">
        <AlbumCardSkeleton layout="compact" />
      </li>
    </ul>
    <template v-else>
      <ul v-if="albums.length" class="results">
        <li v-for="album in albums" :key="album.id">
          <AlbumCard :album="album" layout="compact" />
        </li>
      </ul>
      <p v-else>None found.</p>
    </template>
  </ExcerptResultBlock>
</template>

<script setup lang="ts">
import { toRefs } from 'vue'

import ExcerptResultBlock from '@/components/screens/search/ExcerptResultBlock.vue'
import AlbumCardSkeleton from '@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'
import AlbumCard from '@/components/album/AlbumCard.vue'

const props = withDefaults(defineProps<{ albums?: Album[], searching?: boolean }>(), {
  albums: () => [],
  searching: false,
})

const { albums, searching } = toRefs(props)
</script>

<style scoped lang="postcss">
.results {
  @apply grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3;
}
</style>
