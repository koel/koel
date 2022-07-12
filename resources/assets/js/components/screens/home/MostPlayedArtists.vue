<template>
  <section>
    <h1>Top Artists</h1>
    <ol v-if="artists.length" class="two-cols top-artist-list">
      <li v-for="artist in artists" :key="artist.id">
        <ArtistCard :artist="artist" layout="compact"/>
      </li>
    </ol>
    <p v-else class="text-secondary">No artists found.</p>
  </section>
</template>

<script lang="ts" setup>
import { toRef } from 'vue'
import { overviewStore } from '@/stores'

import ArtistCard from '@/components/artist/ArtistCard.vue'

const artists = toRef(overviewStore.state, 'mostPlayedArtists')
</script>
