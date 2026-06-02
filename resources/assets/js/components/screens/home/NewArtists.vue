<template>
  <Carousel>
    <template #header>New Artists</template>
    <template v-if="loading">
      <ArtistCardSkeleton v-for="i in 6" :key="i" />
    </template>
    <template v-else-if="artists.length">
      <ArtistCard v-for="artist in artists" :key="artist.id" :artist />
    </template>
    <p v-else class="text-k-fg-50">No new artists.</p>
  </Carousel>
</template>

<script lang="ts" setup>
import { toRef, toRefs } from 'vue'
import { overviewStore } from '@/stores/overviewStore'

import ArtistCard from '@/components/artist/ArtistCard.vue'
import ArtistCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import Carousel from '@/components/ui/Carousel.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const artists = toRef(overviewStore.state, 'recentlyAddedArtists')
</script>
