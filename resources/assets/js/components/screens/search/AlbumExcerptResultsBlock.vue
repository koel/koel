<template>
  <SearchResultBlock>
    <template #header>Albums</template>

    <Carousel>
      <template v-if="searching">
        <AlbumCardSkeleton v-for="i in 6" :key="i" />
      </template>
      <template v-else-if="albums.length">
        <AlbumCard v-for="album in albums" :key="album.id" :album />
      </template>
      <p v-else class="text-k-fg-50">None found.</p>
    </Carousel>
  </SearchResultBlock>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

import Carousel from '@/components/ui/Carousel.vue'
import AlbumCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import AlbumCard from '@/components/album/AlbumCard.vue'
import SearchResultBlock from '@/components/screens/search/SearchResultBlock.vue'

const props = withDefaults(defineProps<{ albums?: Album[]; searching?: boolean }>(), {
  albums: () => [],
  searching: false,
})

const { albums, searching } = toRefs(props)
</script>
