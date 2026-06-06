<template>
  <Carousel>
    <template #header>Artists</template>

    <template v-if="searching">
      <ArtistCardSkeleton v-for="i in 6" :key="i" />
    </template>
    <template v-else-if="artists.length">
      <ArtistCard v-for="artist in artists" :key="artist.id" :artist />
    </template>
    <p v-else class="text-k-fg-50">None found.</p>
  </Carousel>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

import ArtistCard from '@/components/artist/ArtistCard.vue'
import ArtistCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import Carousel from '@/components/ui/Carousel.vue'

const props = withDefaults(defineProps<{ artists?: Artist[]; searching?: boolean }>(), {
  artists: () => [],
  searching: false,
})

const { artists, searching } = toRefs(props)
</script>
