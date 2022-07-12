<template>
  <section>
    <h1>New Songs</h1>
    <ol v-if="songs.length" class="recently-added-song-list">
      <li v-for="song in songs" :key="song.id">
        <SongCard :song="song"/>
      </li>
    </ol>
    <p v-else class="text-secondary">No songs added so far.</p>
  </section>
</template>

<script lang="ts" setup>
import { toRef } from 'vue'
import { overviewStore } from '@/stores'

import SongCard from '@/components/song/SongCard.vue'

const songs = toRef(overviewStore.state, 'recentlyAddedSongs')
</script>
