<template>
  <HomeScreenBlock>
    <template #header>Random Albums</template>
    <template #actions>
      <Btn v-if="albums.length" size="small" variant="ghost" rounded :disabled="refreshing" @click.prevent="refresh">
        <Icon :icon="faRotateRight" :class="{ 'animate-spin': refreshing }" />
        <span class="sr-only">Refresh</span>
      </Btn>
    </template>
    <Carousel>
      <template v-if="loading">
        <AlbumCardSkeleton v-for="i in 6" :key="i" />
      </template>
      <template v-else-if="albums.length">
        <AlbumCard v-for="album in albums" :key="album.id" :album />
      </template>
      <p v-else class="text-k-fg-50">No albums yet.</p>
    </Carousel>
  </HomeScreenBlock>
</template>

<script lang="ts" setup>
import { faRotateRight } from '@fortawesome/free-solid-svg-icons'
import { ref, toRef, toRefs } from 'vue'
import { overviewStore } from '@/stores/overviewStore'
import { useErrorHandler } from '@/composables/useErrorHandler'

import AlbumCard from '@/components/album/AlbumCard.vue'
import AlbumCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import Btn from '@/components/ui/form/Btn.vue'
import Carousel from '@/components/ui/Carousel.vue'
import HomeScreenBlock from '@/components/screens/home/HomeScreenBlock.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const albums = toRef(overviewStore.state, 'randomAlbums')
const refreshing = ref(false)

const refresh = async () => {
  refreshing.value = true

  try {
    await overviewStore.refreshRandomAlbums()
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error)
  } finally {
    refreshing.value = false
  }
}
</script>
