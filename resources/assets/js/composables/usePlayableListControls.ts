import { merge } from 'lodash'
import { reactive } from 'vue'

import PlayableListControls from '@/components/playable/playable-list/PlayableListControls.vue'

export const usePlayableListControls = (
  screen: ScreenName,
  configOverrides: Partial<PlayableListControlsConfig> | (() => Partial<PlayableListControlsConfig>) = {},
) => {
  const defaults: PlayableListControlsConfig = {
    addTo: {
      queue: screen !== 'Queue',
      favorites: screen !== 'Favorites',
    },
    clearQueue: screen === 'Queue',
    refresh: screen === 'Playlist',
    filter: [
      'Queue',
      'Artist',
      'Album',
      'Favorites',
      'RecentlyPlayed',
      'Playlist',
      'Search.Playables',
    ].includes(screen),
  }

  const config = merge(defaults, typeof configOverrides === 'function' ? configOverrides() : configOverrides)

  return {
    PlayableListControls,
    config: reactive(config),
  }
}
