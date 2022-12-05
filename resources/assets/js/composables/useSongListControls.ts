import { useRouter } from '@/composables'

export const useSongListControls = () => {
  const { isCurrentScreen } = useRouter()

  const getSongListControlsConfig = () => {

    const config: SongListControlsConfig = {
      play: true,
      addTo: {
        queue: true,
        favorites: true,
      },
      clearQueue: true,
      deletePlaylist: true,
      refresh: true,
    }

    config.clearQueue = isCurrentScreen('Queue')
    config.addTo.queue = !isCurrentScreen('Queue')
    config.addTo.favorites = !isCurrentScreen('Favorites')
    config.deletePlaylist = isCurrentScreen('Playlist')
    config.refresh = isCurrentScreen('Playlist')

    return config
  }

  return {
    getSongListControlsConfig
  }
}
