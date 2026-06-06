<template>
  <Carousel :on-refresh="refresh">
    <template #header>Random Albums</template>
    <template v-if="loading">
      <AlbumCardSkeleton v-for="i in 6" :key="i" />
    </template>
    <template v-else-if="albums.length">
      <AlbumCard v-for="album in albums" :key="album.id" :album />
    </template>
    <p v-else class="text-k-fg-50">No albums yet.</p>
  </Carousel>
</template>

<script lang="ts" setup>
import { toRef, toRefs } from 'vue'
import { overviewStore } from '@/stores/overviewStore'
import { useErrorHandler } from '@/composables/useErrorHandler'

import AlbumCard from '@/components/album/AlbumCard.vue'
import AlbumCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import Carousel from '@/components/ui/Carousel.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const albums = toRef(overviewStore.state, 'randomAlbums')

const refresh = async () => {
  try {
    await overviewStore.refreshRandomAlbums()
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error)
  }
}
</script>
