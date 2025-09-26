export function isSong (streamable: Streamable | Folder): streamable is Song {
  return streamable.type === 'songs'
}

export function isEpisode (streamable: Streamable): streamable is Episode {
  return streamable.type === 'episodes'
}

export function isRadioStation (streamable: Streamable): streamable is RadioStation {
  return streamable.type === 'radio-stations'
}

export function getPlayableCollectionContentType (playables: Playable[]): Song['type'] | Episode['type'] | 'mixed' {
  return new Set(playables.map(playable => playable.type)).size === 1
    ? playables[0].type
    : 'mixed'
}
