export interface TableHeaderMenuItem<Column extends string, Field extends string> {
  column: Column
  label: string
  field?: Field
}

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

export const albumTableMenuItems: readonly TableHeaderMenuItem<AlbumTableColumnName, AlbumListSortField>[] = [
  { column: 'name', label: 'Name', field: 'name' },
  { column: 'artist', label: 'Artist', field: 'artist_name' },
  { column: 'time', label: 'Time', field: 'length' },
  { column: 'year', label: 'Year', field: 'year' },
  { column: 'rating', label: 'Rating', field: 'rating' },
  { column: 'favorite', label: 'Favorite', field: 'favorite' },
]

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

export const artistTableMenuItems: readonly TableHeaderMenuItem<ArtistTableColumnName, ArtistListSortField>[] = [
  { column: 'name', label: 'Name', field: 'name' },
  { column: 'rating', label: 'Rating', field: 'rating' },
  { column: 'favorite', label: 'Favorite', field: 'favorite' },
]

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

export const radioStationTableMenuItems: readonly TableHeaderMenuItem<
  RadioStationTableColumnName,
  RadioStationListSortField
>[] = [
  { column: 'name', label: 'Name', field: 'name' },
  { column: 'description', label: 'Description' },
  { column: 'created_at', label: 'Date Added', field: 'created_at' },
  { column: 'favorite', label: 'Favorite', field: 'favorite' },
]

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
