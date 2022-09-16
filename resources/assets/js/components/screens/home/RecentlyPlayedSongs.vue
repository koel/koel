<template>
  <section class="recent">
    <h1>
      Recently Played
      <Btn
        v-if="songs.length"
        data-testid="home-view-all-recently-played-btn"
        orange
        rounded
        small
        @click.prevent="goToRecentlyPlayedScreen"
      >
        View All
      </Btn>
    </h1>

    <ol v-if="loading" class="recent-song-list">
      <li v-for="i in 3" :key="i">
        <SongCardSkeleton/>
      </li>
    </ol>
    <template v-else>
      <ol v-if="songs.length" class="recent-song-list">
        <li v-for="song in songs" :key="song.id">
          <SongCard :song="song"/>
        </li>
      </ol>
      <p v-else class="text-secondary">No songs played as of late.</p>
    </template>
  </section>
</template>

<script lang="ts" setup>
import { toRef, toRefs } from 'vue'
import router from '@/router'
import { overviewStore } from '@/stores'

import Btn from '@/components/ui/Btn.vue'
import SongCard from '@/components/song/SongCard.vue'
import SongCardSkeleton from '@/components/ui/skeletons/SongCardSkeleton.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const songs = toRef(overviewStore.state, 'recentlyPlayed')

const goToRecentlyPlayedScreen = () => router.go('recently-played')
</script>
