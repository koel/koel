import { pluralize } from '@/utils/formatters'
import { arrayify } from '@/utils/helpers'

export function isSong (playable: Playable): playable is Song {
  return playable.type === 'songs'
}

export function isEpisode (playable: Playable): playable is Episode {
  return playable.type === 'episodes'
}

export function getPlayableCollectionContentType(playables: Playable[]): Song['type'] | Episode['type'] | 'mixed' {
  return new Set(playables.map(playable => playable.type)).size === 1
    ? playables[0].type
    : 'mixed'
}

