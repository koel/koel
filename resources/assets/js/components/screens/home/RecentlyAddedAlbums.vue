<template>
  <section>
    <h1>New Albums</h1>
    <ol class="recently-added-album-list">
      <li v-for="album in albums" :key="album.id">
        <AlbumCard :album="album" layout="compact"/>
      </li>
    </ol>
  </section>
</template>

<script lang="ts" setup>
import { toRef } from 'vue'
import { overviewStore } from '@/stores'

import AlbumCard from '@/components/album/AlbumCard.vue'

const albums = toRef(overviewStore.state, 'recentlyAddedAlbums')
</script>
