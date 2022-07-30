<template>
  <section>
    <h1>New Albums</h1>

    <ol v-if="loading" class="recently-added-album-list">
      <li v-for="i in 2" :key="i">
        <AlbumCardSkeleton layout="compact"/>
      </li>
    </ol>
    <template v-else>
      <ol v-if="albums.length" class="recently-added-album-list">
        <li v-for="album in albums" :key="album.id">
          <AlbumCard :album="album" layout="compact"/>
        </li>
      </ol>
      <p v-else class="text-secondary">No albums added yet.</p>
    </template>
  </section>
</template>

<script lang="ts" setup>
import { toRef } from 'vue'
import { overviewStore } from '@/stores'

import AlbumCard from '@/components/album/AlbumCard.vue'
import AlbumCardSkeleton from '@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'

const albums = toRef(overviewStore.state, 'recentlyAddedAlbums')
const loading = toRef(overviewStore.state, 'loading')
</script>
