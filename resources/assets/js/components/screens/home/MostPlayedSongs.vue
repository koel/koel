<template>
  <section v-if="songs.length">
    <h1>Most Played</h1>

    <ol class="top-song-list">
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

const songs = toRef(overviewStore.state, 'mostPlayedSongs')
</script>
