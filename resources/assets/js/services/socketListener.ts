import { playbackService, socketService, volumeManager } from '@/services'
import { favoriteStore, queueStore } from '@/stores'

export const socketListener = {
  listen: () => {
    socketService
      .listen('SOCKET_TOGGLE_PLAYBACK', () => playbackService.toggle())
      .listen('SOCKET_PLAY_NEXT', () => playbackService.playNext())
      .listen('SOCKET_PLAY_PREV', () => playbackService.playPrev())
      .listen('SOCKET_GET_STATUS', () => {
        socketService.broadcast('SOCKET_STATUS', {
          playable: queueStore.current,
          volume: volumeManager.get()
        })
      })
      .listen('SOCKET_GET_CURRENT_PLAYABLE', () => socketService.broadcast('SOCKET_PLAYABLE', queueStore.current))
      .listen('SOCKET_SET_VOLUME', (volume: number) => volumeManager.set(volume))
      .listen('SOCKET_TOGGLE_FAVORITE', () => queueStore.current && favoriteStore.toggleOne(queueStore.current))
  }
}
