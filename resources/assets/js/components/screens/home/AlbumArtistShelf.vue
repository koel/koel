<template>
  <ShelfSection>
    <template #header>{{ header }}</template>
    <template #actions>
      <ShelfRefreshButton v-if="refresh && entities.length" :refresh />
    </template>
    <Carousel>
      <template v-if="loading">
        <ArtistAlbumCardSkeleton v-for="i in 6" :key="i" />
      </template>
      <template v-else-if="entities.length">
        <template v-for="entity in entities" :key="entity.id">
          <AlbumCard v-if="entity.type === 'albums'" :album="entity" />
          <ArtistCard v-else :artist="entity" />
        </template>
      </template>
      <p v-else class="text-k-fg-50">{{ emptyText }}</p>
    </Carousel>
  </ShelfSection>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { overviewStore } from '@/stores/overviewStore'

import AlbumCard from '@/components/album/AlbumCard.vue'
import ArtistCard from '@/components/artist/ArtistCard.vue'
import ArtistAlbumCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import Carousel from '@/components/ui/Carousel.vue'
import ShelfSection from '@/components/ui/ShelfSection.vue'
import ShelfRefreshButton from '@/components/screens/home/ShelfRefreshButton.vue'

type OverviewState = typeof overviewStore.state

type AlbumArtistStateKey = {
  [Key in keyof OverviewState]: OverviewState[Key] extends Album[] | Artist[] ? Key : never
}[keyof OverviewState]

const props = withDefaults(
  defineProps<{
    header: string
    stateKey: AlbumArtistStateKey
    emptyText: string
    refresh?: () => Promise<unknown>
    loading?: boolean
  }>(),
  { loading: false },
)

const { header, emptyText, refresh, loading } = toRefs(props)

const entities = computed<(Album | Artist)[]>(() => overviewStore.state[props.stateKey])
</script>
