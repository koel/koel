<template>
  <button type="button" :class="playing ? 'playing' : 'stopped'" title="Play or resume" @click.prevent="toggle">
    <icon v-if="playing" :icon="faPause"/>
    <icon v-else :icon="faPlay"/>
  </button>
</template>

<script lang="ts" setup>
import { faPause, faPlay } from '@fortawesome/free-solid-svg-icons'
import { computed, ref } from 'vue'
import { playbackService } from '@/services'
import { commonStore, favoriteStore, queueStore, recentlyPlayedStore, songStore } from '@/stores'
import { requireInjection } from '@/utils'
import { CurrentSongKey, RouterKey } from '@/symbols'

const router = requireInjection(RouterKey)
const song = requireInjection(CurrentSongKey, ref(null))

const libraryEmpty = computed(() => commonStore.state.song_count === 0)
const playing = computed(() => song.value?.playback_state === 'Playing')

const toggle = async () => song.value ? playbackService.toggle() : initiatePlayback()

const initiatePlayback = async () => {
  if (libraryEmpty.value) return

  let songs: Song[]

  switch (router.$currentRoute.value.screen) {
    case 'Album':
      songs = await songStore.fetchForAlbum(parseInt(router.$currentRoute.value.params!.id))
      break
    case 'Artist':
      songs = await songStore.fetchForArtist(parseInt(router.$currentRoute.value.params!.id))
      break
    case 'Playlist':
      songs = await songStore.fetchForPlaylist(parseInt(router.$currentRoute.value.params!.id))
      break
    case 'Favorites':
      songs = await favoriteStore.fetch()
      break
    case 'RecentlyPlayed':
      songs = await recentlyPlayedStore.fetch()
      break
    default:
      songs = await queueStore.fetchRandom()
      break
  }

  await playbackService.queueAndPlay(songs)
  router.go('queue')
}
</script>

<style lang="scss" scoped>
button {
  width: 3rem;
  border-radius: 50%;
  border: 2px solid currentColor;

  &.stopped {
    text-indent: 0.2rem;
  }

  &:hover {
    border-color: var(--color-text-primary) !important;
    transform: scale(1.2);
  }
}
</style>
