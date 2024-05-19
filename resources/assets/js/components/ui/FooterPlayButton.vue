<template>
  <FooterButton
    :title="playing ? 'Pause' : 'Play or resume'"
    class="!w-[3rem] rounded-full border-2 border-solid aspect-square !transition-transform hover:scale-125 !text-2xl
    has-[.icon-play]:indent-[0.23rem]"
    @click.prevent="toggle"
  >
    <Icon v-if="playing" :icon="faPause" />
    <Icon v-else :icon="faPlay" class="icon-play" />
  </FooterButton>
</template>

<script lang="ts" setup>
import { faPause, faPlay } from '@fortawesome/free-solid-svg-icons'
import { computed, ref } from 'vue'
import { playbackService } from '@/services'
import { commonStore, favoriteStore, queueStore, recentlyPlayedStore, songStore } from '@/stores'
import { requireInjection } from '@/utils'
import { useRouter } from '@/composables'
import { CurrentPlayableKey } from '@/symbols'
import FooterButton from '@/components/layout/app-footer/FooterButton.vue'

const { getCurrentScreen, getRouteParam, go } = useRouter()
const song = requireInjection(CurrentPlayableKey, ref())

const libraryEmpty = computed(() => commonStore.state.song_count === 0)
const playing = computed(() => song.value?.playback_state === 'Playing')

const toggle = async () => song.value ? playbackService.toggle() : initiatePlayback()

const initiatePlayback = async () => {
  if (libraryEmpty.value) return

  let songs: Song[]

  switch (getCurrentScreen()) {
    case 'Album':
      songs = await songStore.fetchForAlbum(parseInt(getRouteParam('id')!))
      break
    case 'Artist':
      songs = await songStore.fetchForArtist(parseInt(getRouteParam('id')!))
      break
    case 'Playlist':
      songs = await songStore.fetchForPlaylist(getRouteParam('id')!)
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

  playbackService.queueAndPlay(songs)
  go('queue')
}
</script>
