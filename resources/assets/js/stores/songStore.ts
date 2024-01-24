import isMobile from 'ismobilejs'
import slugify from 'slugify'
import { merge, orderBy, sumBy, take, unionBy, uniqBy } from 'lodash'
import { reactive, UnwrapNestedRefs, watch } from 'vue'
import { arrayify, logger, secondsToHumanReadable, use } from '@/utils'
import { authService, cache, http } from '@/services'
import { albumStore, artistStore, commonStore, overviewStore, playlistStore, preferenceStore } from '@/stores'

export type SongUpdateData = {
  title?: string
  artist_name?: string
  album_name?: string
  album_artist_name?: string
  track?: number | null
  disc?: number | null
  lyrics?: string
  year?: number | null
  genre?: string
  visibility?: 'public' | 'private' | 'unchanged'
}

export interface SongUpdateResult {
  songs: Song[]
  artists: Artist[]
  albums: Album[]
  removed: {
    albums: Pick<Album, 'id' | 'artist_id' | 'name' | 'cover' | 'created_at'>[]
    artists: Pick<Artist, 'id' | 'name' | 'image' | 'created_at'>[]
  }
}

export interface SongListPaginateParams extends Record<string, any> {
  sort: SongListSortField
  order: SortOrder
  page: number
  own_songs_only: boolean
}

export interface GenreSongListPaginateParams extends Record<string, any> {
  sort: SongListSortField
  order: SortOrder
  page: number
}

export const songStore = {
  vault: new Map<string, UnwrapNestedRefs<Song>>(),

  state: reactive({
    songs: [] as Song[]
  }),

  getFormattedLength: (songs: Song | Song[]) => secondsToHumanReadable(sumBy(arrayify(songs), 'length')),

  byId (id: string) {
    const song = this.vault.get(id)
    return song?.deleted ? undefined : song
  },

  byIds (ids: string[]) {
    const songs = [] as Song[]
    ids.forEach(id => use(this.byId(id), song => songs.push(song!)))
    return songs
  },

  byAlbum (album: Album) {
    return Array.from(this.vault.values()).filter(({ album_id }) => album_id === album.id)
  },

  async resolve (id: string) {
    let song = this.byId(id)

    if (!song) {
      try {
        song = this.syncWithVault(await http.get<Song>(`songs/${id}`))[0]
      } catch (e) {
        logger.error(e)
      }
    }

    return song
  },

  /**
   * Match a title to a song.
   * Forget about Levenshtein distance, this implementation is good enough.
   */
  match: (title: string, songs: Song[]) => {
    title = slugify(title.toLowerCase())

    for (const song of songs) {
      if (slugify(song.title.toLowerCase()) === title) {
        return song
      }
    }

    return null
  },

  /**
   * Increase a play count for a song.
   */
  registerPlay: async (song: Song) => {
    const interaction = await http.silently.post<Interaction>('interaction/play', { song: song.id })

    // Use the data from the server to make sure we don't miss a play from another device.
    song.play_count = interaction.play_count
  },

  scrobble: async (song: Song) => await http.silently.post(`songs/${song.id}/scrobble`, {
    timestamp: song.play_start_time
  }),

  async update (songsToUpdate: Song[], data: SongUpdateData) {
    const { songs, artists, albums, removed } = await http.put<SongUpdateResult>('songs', {
      data,
      songs: songsToUpdate.map(song => song.id)
    })

    this.syncWithVault(songs)

    albumStore.syncWithVault(albums)
    artistStore.syncWithVault(artists)

    albumStore.removeByIds(removed.albums.map(album => album.id))
    artistStore.removeByIds(removed.artists.map(artist => artist.id))
  },

  getSourceUrl: (song: Song) => {
    return isMobile.any && preferenceStore.transcode_on_mobile
      ? `${commonStore.state.cdn_url}play/${song.id}/1/128?t=${authService.getAudioToken()}`
      : `${commonStore.state.cdn_url}play/${song.id}?t=${authService.getAudioToken()}`
  },

  getShareableUrl: (song: Song) => `${window.BASE_URL}#/song/${song.id}`,

  syncWithVault (songs: Song | Song[]) {
    return arrayify(songs).map(song => {
      let local = this.byId(song.id)

      if (local) {
        merge(local, song)
      } else {
        local = reactive(song)
        local.playback_state = 'Stopped'
        this.watchPlayCount(local)
        this.vault.set(local.id, local)
      }

      return local
    })
  },

  watchPlayCount: (song: UnwrapNestedRefs<Song>) => {
    watch(() => song.play_count, () => overviewStore.refresh())
  },

  ensureNotDeleted: (songs: Song | Song[]) => arrayify(songs).filter(({ deleted }) => !deleted),

  async fetchForAlbum (album: Album | number) {
    const id = typeof album === 'number' ? album : album.id

    return this.ensureNotDeleted(await cache.remember<Song[]>(
      [`album.songs`, id],
      async () => this.syncWithVault(await http.get<Song[]>(`albums/${id}/songs`))
    ))
  },

  async fetchForArtist (artist: Artist | number) {
    const id = typeof artist === 'number' ? artist : artist.id

    return this.ensureNotDeleted(await cache.remember<Song[]>(
      [`artist.songs`, id],
      async () => this.syncWithVault(await http.get<Song[]>(`artists/${id}/songs`))
    ))
  },

  async fetchForPlaylist (playlist: Playlist | string, refresh = false) {
    const id = typeof playlist === 'string' ? playlist : playlist.id

    if (refresh) {
      cache.remove(['playlist.songs', id])
    }

    return this.ensureNotDeleted(await cache.remember<Song[]>(
      [`playlist.songs`, id],
      async () => this.syncWithVault(await http.get<Song[]>(`playlists/${id}/songs`))
    ))
  },

  async fetchForPlaylistFolder (folder: PlaylistFolder) {
    const songs: Song[] = []

    for await (const playlist of playlistStore.byFolder(folder)) {
      songs.push(...await songStore.fetchForPlaylist(playlist))
    }

    return uniqBy(songs, 'id')
  },

  async paginateForGenre (genre: Genre | string, params: GenreSongListPaginateParams) {
    const name = typeof genre === 'string' ? genre : genre.name
    const resource = await http.get<PaginatorResource>(`genres/${name}/songs?${new URLSearchParams(params).toString()}`)
    const songs = this.syncWithVault(resource.data)

    return {
      songs,
      nextPage: resource.links.next ? ++resource.meta.current_page : null
    }
  },

  async fetchRandomForGenre (genre: Genre | string, limit = 500) {
    const name = typeof genre === 'string' ? genre : genre.name
    return this.syncWithVault(await http.get<Song[]>(`genres/${name}/songs/random?limit=${limit}`))
  },

  async paginate (params: SongListPaginateParams) {
    const resource = await http.get<PaginatorResource>(`songs?${new URLSearchParams(params).toString()}`)
    this.state.songs = unionBy(this.state.songs, this.syncWithVault(resource.data), 'id')

    return resource.links.next ? ++resource.meta.current_page : null
  },

  getMostPlayed (count: number) {
    return take(
      orderBy(
        Array.from(this.vault.values()).filter(({ deleted, play_count })=> !deleted && play_count > 0),
        'play_count',
        'desc'
      ),
      count
    )
  },

  async deleteFromFilesystem (songs: Song[]) {
    const ids = songs.map(song => {
      // Whenever a vault sync is requested (e.g. upon playlist/album/artist fetching)
      // songs marked as "deleted" will be excluded.
      song.deleted = true
      return song.id
    })

    await http.delete('songs', { songs: ids })
  },

  async publicize (songs: Song[]) {
    await http.put('songs/publicize', {
      songs: songs.map(song => song.id)
    })

    songs.forEach(song => song.is_public = true)
  },

  async privatize (songs: Song[]) {
    await http.put('songs/privatize', {
      songs: songs.map(song => song.id)
    })

    songs.forEach(song => song.is_public = false)
  }
}
