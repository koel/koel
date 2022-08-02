import { playbackService, socketService } from '@/services'
import { favoriteStore, queueStore } from '@/stores'

export const socketListener = {
  listen: () => {
    socketService
      .listen('SOCKET_TOGGLE_PLAYBACK', () => playbackService.toggle())
      .listen('SOCKET_PLAY_NEXT', () => playbackService.playNext())
      .listen('SOCKET_PLAY_PREV', () => playbackService.playPrev())
      .listen('SOCKET_GET_STATUS', () => {
        socketService.broadcast('SOCKET_STATUS', {
          song: queueStore.current,
          volume: playbackService.getVolume()
        })
      })
      .listen('SOCKET_GET_CURRENT_SONG', () => socketService.broadcast('SOCKET_SONG', queueStore.current))
      .listen('SOCKET_SET_VOLUME', (volume: number) => playbackService.setVolume(volume))
      .listen('SOCKET_TOGGLE_FAVORITE', () => queueStore.current && favoriteStore.toggleOne(queueStore.current))
  }
}
