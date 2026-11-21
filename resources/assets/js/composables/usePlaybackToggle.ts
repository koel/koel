import type { Ref } from 'vue'
import { computed } from 'vue'
import { commonStore } from '@/stores/commonStore'
import { queueStore } from '@/stores/queueStore'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import { playableStore } from '@/stores/playableStore'
import { useRouter } from '@/composables/useRouter'
import { playback } from '@/services/playbackManager'

export const usePlaybackToggle = (streamable: Ref<Streamable | undefined>) => {
  const { getCurrentScreen, getRouteParam, go, url } = useRouter()

  const libraryEmpty = computed(() => commonStore.state.song_count === 0)
  const isRadio = computed(() => streamable.value?.type === 'radio-stations')

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

  const togglePlayback = async () => {
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

  return {
    togglePlayback,
  }
}
