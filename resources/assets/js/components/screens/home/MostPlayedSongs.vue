<template>
  <section>
    <h1>Most Played</h1>
    <ol v-if="songs.length" class="top-song-list">
      <li v-for="song in songs" :key="song.id">
        <SongCard :song="song"/>
      </li>
    </ol>
    <p v-else class="text-secondary">You don’t seem to have been playing.</p>
  </section>
</template>

<script lang="ts" setup>
import { toRef } from 'vue'
import { overviewStore } from '@/stores'

import SongCard from '@/components/song/SongCard.vue'

const songs = toRef(overviewStore.state, 'mostPlayedSongs')
</script>
