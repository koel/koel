<template>
  <section>
    <h1>Most Played</h1>
    <ol v-if="loading" class="top-song-list">
      <li v-for="i in 3" :key="i">
        <SongCardSkeleton/>
      </li>
    </ol>
    <template v-else>
      <ol v-if="songs.length" class="top-song-list">
        <li v-for="song in songs" :key="song.id">
          <SongCard :song="song"/>
        </li>
      </ol>
      <p v-else class="text-secondary">You don’t seem to have been playing.</p>
    </template>
  </section>
</template>

<script lang="ts" setup>
import { toRef, toRefs } from 'vue'
import { overviewStore } from '@/stores'
import SongCard from '@/components/song/SongCard.vue'
import SongCardSkeleton from '@/components/ui/skeletons/SongCardSkeleton.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const songs = toRef(overviewStore.state, 'mostPlayedSongs')
</script>
