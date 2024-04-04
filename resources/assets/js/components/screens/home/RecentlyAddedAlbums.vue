<template>
  <HomeScreenBlock>
    <template #header>New Albums</template>

    <ol v-if="loading" class="space-y-3">
      <li v-for="i in 2" :key="i">
        <AlbumCardSkeleton layout="compact" />
      </li>
    </ol>
    <template v-else>
      <ol v-if="albums.length" class="space-y-3">
        <li v-for="album in albums" :key="album.id">
          <AlbumCard :album="album" layout="compact" />
        </li>
      </ol>
      <p v-else class="text-k-text-secondary">No albums added yet.</p>
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

const albums = toRef(overviewStore.state, 'recentlyAddedAlbums')
</script>
