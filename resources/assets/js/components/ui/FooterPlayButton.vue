<template>
  <FooterButton
    :title
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
import { commonStore } from '@/stores/commonStore'
import { queueStore } from '@/stores/queueStore'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import { playableStore } from '@/stores/playableStore'
import { useRouter } from '@/composables/useRouter'
import { requireInjection } from '@/utils/helpers'
import { CurrentStreamableKey } from '@/symbols'
import { playback } from '@/services/playbackManager'

import FooterButton from '@/components/layout/app-footer/FooterButton.vue'

const { getCurrentScreen, getRouteParam, go, url } = useRouter()
const streamable = requireInjection(CurrentStreamableKey, ref())

const libraryEmpty = computed(() => commonStore.state.song_count === 0)
const playing = computed(() => streamable.value?.playback_state === 'Playing')
const isRadio = computed(() => streamable.value?.type === 'radio-stations')

const title = computed(() => {
  if (isRadio.value) {
    return streamable.value?.playback_state === 'Playing' ? 'Stop streaming' : 'Start streaming'
  }

  return playing.value ? 'Pause' : 'Play or resume'
})

const initiatePlayback = async () => {
  if (libraryEmpty.value) {
    return
  }

  let playables: Playable[]

  switch (getCurrentScreen()) {
    case 'Album':
      playables = await playableStore.fetchSongsForAlbum(getRouteParam('id')!)
      break
    case 'Artist':
      playables = await playableStore.fetchSongsForArtist(getRouteParam('id')!)
      break
    case 'Playlist':
      playables = await playableStore.fetchForPlaylist(getRouteParam('id')!)
      break
    case 'Favorites':
      playables = await playableStore.fetchFavorites()
      break
    case 'RecentlyPlayed':
      playables = await recentlyPlayedStore.fetch()
      break
    case 'Genre':
      playables = await playableStore.fetchSongsByGenre(getRouteParam('id')!)
      break
    default:
      playables = await queueStore.fetchRandom()
      break
  }

  await playback().queueAndPlay(playables)
  go(url('queue'))
}

const toggle = async () => {
  if (!streamable.value) {
    await initiatePlayback()
    return
  }

  if (isRadio.value) {
    await playback('radio').toggle()
    return
  }

  await playback('queue').toggle()
}
</script>
