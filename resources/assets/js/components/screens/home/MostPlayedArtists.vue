<template>
  <section>
    <h1>Top Artists</h1>
    <ol class="two-cols top-artist-list">
      <li v-for="artist in artists" :key="artist.id">
        <ArtistCard :artist="artist" layout="compact"/>
      </li>
    </ol>
  </section>
</template>

<script lang="ts" setup>
import { toRef } from 'vue'
import { overviewStore } from '@/stores'

import ArtistCard from '@/components/artist/ArtistCard.vue'

const artists = toRef(overviewStore.state, 'mostPlayedArtists')
</script>
