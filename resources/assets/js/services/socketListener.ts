import { socketService } from '@/services/socketService'
import { volumeManager } from '@/services/volumeManager'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { playback } from '@/services/playbackManager'
import { radioStationStore } from '@/stores/radioStationStore'
import { RadioPlaybackService } from '@/services/RadioPlaybackService'

const playingRadio = () => playback('current') instanceof RadioPlaybackService

export const socketListener = {
  listen: () => {
    socketService
      .listen('SOCKET_TOGGLE_PLAYBACK', () => playback('current')?.toggle())
      .listen('SOCKET_PLAY_NEXT', () => playback('current')?.playNext())
      .listen('SOCKET_PLAY_PREV', () => playback('current')?.playPrev())
      .listen('SOCKET_GET_STATUS', () => {
        socketService.broadcast('SOCKET_STATUS', {
          streamable: playingRadio() ? radioStationStore.current : queueStore.current,
          volume: volumeManager.get(),
        })
      })
      .listen('SOCKET_GET_CURRENT_PLAYABLE', () => {
        socketService.broadcast(
          'SOCKET_STREAMABLE',
          playingRadio() ? radioStationStore.current : queueStore.current,
        )
      })
      .listen('SOCKET_SET_VOLUME', (volume: number) => volumeManager.set(volume))
      .listen('SOCKET_TOGGLE_FAVORITE', async () => {
        if (playingRadio()) {
          await radioStationStore.toggleFavorite(radioStationStore.current!)
        } else {
          await playableStore.toggleFavorite(queueStore.current!)
        }
      })
  },
}
