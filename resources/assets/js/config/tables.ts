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
