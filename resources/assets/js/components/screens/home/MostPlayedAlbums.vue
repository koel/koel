<template>
  <section>
    <h1>Top Albums</h1>

    <ol v-if="loading" class="two-cols top-album-list">
      <li v-for="i in 4" :key="i">
        <AlbumCardSkeleton layout="compact" />
      </li>
    </ol>
    <template v-else>
      <ol v-if="albums.length" class="two-cols top-album-list">
        <li v-for="album in albums" :key="album.id">
          <AlbumCard :album="album" layout="compact" />
        </li>
      </ol>
      <p v-else class="text-secondary">No albums found.</p>
    </template>
  </section>
</template>

<script lang="ts" setup>
import { toRef, toRefs } from 'vue'
import { overviewStore } from '@/stores'
import AlbumCard from '@/components/album/AlbumCard.vue'
import AlbumCardSkeleton from '@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const albums = toRef(overviewStore.state, 'mostPlayedAlbums')
</script>
