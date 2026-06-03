export const albumTableColumnConfig = {
  storageKey: 'album-table-columns',
  validColumns: ['name', 'artist', 'time', 'year', 'rating', 'favorite'] as const,
  defaultColumns: ['name', 'artist', 'rating', 'favorite'] as const,
  alwaysVisible: ['name'] as const,
} satisfies {
  storageKey: string
  validColumns: readonly AlbumTableColumnName[]
  defaultColumns: readonly AlbumTableColumnName[]
  alwaysVisible: readonly AlbumTableColumnName[]
}

export const artistTableColumnConfig = {
  storageKey: 'artist-table-columns',
  validColumns: ['name', 'rating', 'favorite'] as const,
  defaultColumns: ['name', 'rating', 'favorite'] as const,
  alwaysVisible: ['name'] as const,
} satisfies {
  storageKey: string
  validColumns: readonly ArtistTableColumnName[]
  defaultColumns: readonly ArtistTableColumnName[]
  alwaysVisible: readonly ArtistTableColumnName[]
}

export const radioStationTableColumnConfig = {
  storageKey: 'radio-station-table-columns',
  validColumns: ['name', 'description', 'created_at', 'favorite'] as const,
  defaultColumns: ['name', 'description', 'favorite'] as const,
  alwaysVisible: ['name'] as const,
} satisfies {
  storageKey: string
  validColumns: readonly RadioStationTableColumnName[]
  defaultColumns: readonly RadioStationTableColumnName[]
  alwaysVisible: readonly RadioStationTableColumnName[]
}

export const playableListColumnConfig = {
  storageKey: 'playable-list-columns',
  validColumns: [
    'track',
    'genre',
    'year',
    'title',
    'artist',
    'album',
    'duration',
    'play_count',
    'rating',
    'favorite',
    'playlist_collaborator',
    'playlist_added_at',
  ] as const,
  defaultColumns: [
    'track',
    'title',
    'artist',
    'album',
    'duration',
    'favorite',
    'playlist_collaborator',
    'playlist_added_at',
  ] as const,
  alwaysVisible: ['title'] as const,
  responsive: true,
} satisfies {
  storageKey: string
  validColumns: readonly PlayableListColumnName[]
  defaultColumns: readonly PlayableListColumnName[]
  alwaysVisible: readonly PlayableListColumnName[]
  responsive: boolean
}
