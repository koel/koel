<template>
  <HomeScreenBlock>
    <template #header>Random Artists</template>
    <template #actions>
      <Btn v-if="artists.length" size="small" variant="ghost" rounded :disabled="refreshing" @click.prevent="refresh">
        <Icon :icon="faRotateRight" :class="{ 'animate-spin': refreshing }" />
        <span class="sr-only">Refresh</span>
      </Btn>
    </template>
    <Carousel>
      <template v-if="loading">
        <ArtistCardSkeleton v-for="i in 6" :key="i" />
      </template>
      <template v-else-if="artists.length">
        <ArtistCard v-for="artist in artists" :key="artist.id" :artist />
      </template>
      <p v-else class="text-k-fg-50">No artists yet.</p>
    </Carousel>
  </HomeScreenBlock>
</template>

<script lang="ts" setup>
import { faRotateRight } from '@fortawesome/free-solid-svg-icons'
import { ref, toRef, toRefs } from 'vue'
import { overviewStore } from '@/stores/overviewStore'
import { useErrorHandler } from '@/composables/useErrorHandler'

import ArtistCard from '@/components/artist/ArtistCard.vue'
import ArtistCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import Btn from '@/components/ui/form/Btn.vue'
import Carousel from '@/components/ui/Carousel.vue'
import HomeScreenBlock from '@/components/screens/home/HomeScreenBlock.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const artists = toRef(overviewStore.state, 'randomArtists')
const refreshing = ref(false)

const refresh = async () => {
  refreshing.value = true

  try {
    await overviewStore.refreshRandomArtists()
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error)
  } finally {
    refreshing.value = false
  }
}
</script>
