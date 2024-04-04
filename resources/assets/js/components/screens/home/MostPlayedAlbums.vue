<template>
  <HomeScreenBlock>
    <template #header>Top Albums</template>

    <ol v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <li v-for="i in 4" :key="i">
        <AlbumCardSkeleton layout="compact" />
      </li>
    </ol>
    <template v-else>
      <ol v-if="albums.length" class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
        <li v-for="album in albums" :key="album.id">
          <AlbumCard :album="album" layout="compact" />
        </li>
      </ol>
      <p v-else class="text-k-text-secondary">No albums found.</p>
    </template>
  </HomeScreenBlock>
</template>

<script lang="ts" setup>
import { toRef, toRefs } from 'vue'
import { overviewStore } from '@/stores'

import AlbumCard from '@/components/album/AlbumCard.vue'
import AlbumCardSkeleton from '@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'
import HomeScreenBlock from '@/components/screens/home/HomeScreenBlock.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const albums = toRef(overviewStore.state, 'mostPlayedAlbums')
</script>
