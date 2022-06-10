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
import { defineAsyncComponent, toRef } from 'vue'
import { overviewStore } from '@/stores'

const AlbumCard = defineAsyncComponent(() => import('@/components/album/AlbumCard.vue'))

const albums = toRef(overviewStore.state, 'recentlyAddedAlbums')
</script>
