<template>
  <section>
    <h1>New Songs</h1>
    <ol v-show="songs.length" class="recently-added-song-list">
      <li v-for="song in songs" :key="song.id">
        <SongCard :song="song"/>
      </li>
    </ol>
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, toRef } from 'vue'
import { overviewStore } from '@/stores'

const SongCard = defineAsyncComponent(() => import('@/components/song/SongCard.vue'))

const songs = toRef(overviewStore.state, 'recentlyAddedSongs')
</script>
