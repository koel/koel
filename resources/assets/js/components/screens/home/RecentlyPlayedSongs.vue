<template>
  <section class="recent">
    <h1>
      Recently Played
      <Btn
        data-testid="home-view-all-recently-played-btn"
        @click.prevent="goToRecentlyPlayedScreen"
        rounded
        small
        orange
      >
        View All
      </Btn>
    </h1>

    <ol v-if="songs.length" class="recent-song-list">
      <li v-for="song in songs" :key="song.id">
        <SongCard :song="song"/>
      </li>
    </ol>

    <p v-show="!songs.length" class="text-secondary">
      Your recently played songs will be displayed here.<br/>
      Start listening!
    </p>
  </section>
</template>

<script lang="ts" setup>
import { toRef } from 'vue'
import router from '@/router'
import { recentlyPlayedStore } from '@/stores'

import Btn from '@/components/ui/Btn.vue'
import SongCard from '@/components/song/SongCard.vue'

const songs = toRef(recentlyPlayedStore.excerptState, 'songs')

const goToRecentlyPlayedScreen = () => router.go('recently-played')
</script>
