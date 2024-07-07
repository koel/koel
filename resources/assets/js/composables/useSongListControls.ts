import { merge } from 'lodash'
import { reactive } from 'vue'

import SongListControls from '@/components/song/SongListControls.vue'

export const useSongListControls = (
  screen: ScreenName,
  configOverrides: Partial<SongListControlsConfig> | (() => Partial<SongListControlsConfig>) = {}
) => {
  const defaults: SongListControlsConfig = {
    addTo: {
      queue: screen !== 'Queue',
      favorites: screen !== 'Favorites',
    },
    clearQueue: screen === 'Queue',
    deletePlaylist: screen === 'Playlist',
    refresh: screen === 'Playlist',
    filter: [
      'Queue',
      'Artist',
      'Album',
      'Favorites',
      'RecentlyPlayed',
      'Playlist',
      'Search.Songs'
    ].includes(screen)
  }

  const config = merge(defaults, typeof configOverrides === 'function' ? configOverrides() : configOverrides)

  return {
    SongListControls,
    config: reactive(config)
  }
}
