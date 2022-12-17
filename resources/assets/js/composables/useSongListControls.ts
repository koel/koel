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
      clearQueue: false,
      deletePlaylist: false,
      refresh: false,
      filter: false
    }

    config.clearQueue = isCurrentScreen('Queue')
    config.addTo.queue = !isCurrentScreen('Queue')
    config.addTo.favorites = !isCurrentScreen('Favorites')
    config.deletePlaylist = isCurrentScreen('Playlist')
    config.refresh = isCurrentScreen('Playlist')

    config.filter = isCurrentScreen(
      'Queue',
      'Artist',
      'Album',
      'Favorites',
      'RecentlyPlayed',
      'Playlist',
      'Search.Songs'
    )

    return config
  }

  return {
    getSongListControlsConfig
  }
}
