import isMobile from 'ismobilejs'
import slugify from 'slugify'
import { merge, orderBy, sumBy, take, unionBy, uniqBy } from 'lodash'
import { reactive, watch } from 'vue'
import { arrayify, isSong, logger, secondsToHumanReadable, use } from '@/utils'
import { authService, cache, http } from '@/services'
import { albumStore, artistStore, commonStore, overviewStore, playlistStore, preferenceStore } from '@/stores'

export interface SongUpdateData {
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
  sort: MaybeArray<PlayableListSortField>
  order: SortOrder
  page: number
  own_songs_only: boolean
}

export interface GenreSongListPaginateParams extends Record<string, any> {
  sort: MaybeArray<PlayableListSortField>
  order: SortOrder
  page: number
}

export const songStore = {
  vault: new Map<Playable['id'], Playable>(),

  state: reactive<{ songs: Playable[] }>({
    songs: [],
  }),

  getFormattedLength: (playables: MaybeArray<Playable>) => secondsToHumanReadable(sumBy(arrayify(playables), 'length')),

  byId (id: Playable['id']) {
    const playable = this.vault.get(id)

    if (!playable) {
      return
    }
    if (isSong(playable) && playable.deleted) {
      return
    }
    return playable
  },

  byIds (ids: Playable['id'][]) {
    const playables: Playable[] = []
    ids.forEach(id => use(this.byId(id), song => playables.push(song!)))
    return playables
  },

  byAlbum (album: Album) {
    return Array.from(this.vault.values())
      .filter(playable => isSong(playable) && playable.album_id === album.id) as Song[]
  },

  async resolve (id: Playable['id']) {
    let playable = this.byId(id)

    if (!playable) {
      try {
        playable = this.syncWithVault(await http.get<Playable>(`songs/${id}`))[0]
      } catch (error: unknown) {
        logger.error(error)
      }
    }

    return playable
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
   * Increase a play count for a playable.
   */
  registerPlay: async (playable: Playable) => {
    const interaction = await http.silently.post<Interaction>('interaction/play', { song: playable.id })

    // Use the data from the server to make sure we don't miss a play from another device.
    playable.play_count = interaction.play_count
  },

  scrobble: async (song: Song) => {
    if (!isSong(song)) {
      throw new Error('Scrobble is only supported for songs.')
    }

    return await http.silently.post(`songs/${song.id}/scrobble`, {
      timestamp: song.play_start_time,
    })
  },

  async update (songsToUpdate: Song[], data: SongUpdateData) {
    if (songsToUpdate.some(song => !isSong(song))) {
      throw new Error('Only songs can be updated.')
    }

    const result = await http.put<SongUpdateResult>('songs', {
      data,
      songs: songsToUpdate.map(song => song.id),
    })

    this.syncWithVault(result.songs)

    albumStore.syncWithVault(result.albums)
    artistStore.syncWithVault(result.artists)

    albumStore.removeByIds(result.removed.albums.map(album => album.id))
    artistStore.removeByIds(result.removed.artists.map(artist => artist.id))

    return result
  },

  getSourceUrl: (playable: Playable) => {
    return isMobile.any && preferenceStore.transcode_on_mobile
      ? `${commonStore.state.cdn_url}play/${playable.id}/1?t=${authService.getAudioToken()}`
      : `${commonStore.state.cdn_url}play/${playable.id}?t=${authService.getAudioToken()}`
  },

  getShareableUrl: (song: Playable) => `${window.BASE_URL}#/song/${song.id}`,

  syncWithVault (playables: MaybeArray<Playable>) {
    return arrayify(playables).map(playable => {
      let local = this.byId(playable.id)

      if (local) {
        merge(local, playable)
      } else {
        local = reactive(playable)
        local.playback_state = 'Stopped'
        this.watchPlayCount(local)
        this.vault.set(local.id, local)
      }

      return local
    })
  },

  watchPlayCount: (playable: Playable) => {
    watch(() => playable.play_count, () => overviewStore.refreshPlayStats())
  },

  ensureNotDeleted: (songs: MaybeArray<Song>) => arrayify(songs).filter(({ deleted }) => !deleted),

  async fetchForAlbum (album: Album | Album['id']) {
    const id = typeof album === 'number' ? album : album.id

    return this.ensureNotDeleted(await cache.remember<Song[]>(
      [`album.songs`, id],
      async () => this.syncWithVault(await http.get<Song[]>(`albums/${id}/songs`)),
    ))
  },

  async fetchForArtist (artist: Artist | Artist['id']) {
    const id = typeof artist === 'number' ? artist : artist.id

    return this.ensureNotDeleted(await cache.remember<Song[]>(
      [`artist.songs`, id],
      async () => this.syncWithVault(await http.get<Song[]>(`artists/${id}/songs`)),
    ))
  },

  async fetchForPlaylist (playlist: Playlist | Playlist['id'], refresh = false) {
    const id = typeof playlist === 'string' ? playlist : playlist.id

    if (refresh) {
      cache.remove(['playlist.songs', id])
    }

    const songs = this.ensureNotDeleted(await cache.remember<Song[]>(
      [`playlist.songs`, id],
      async () => this.syncWithVault(await http.get<Song[]>(`playlists/${id}/songs`)),
    ))

    playlistStore.byId(id)!.playables = songs

    return songs
  },

  async fetchForPlaylistFolder (folder: PlaylistFolder) {
    const playables: Playable[] = []

    for await (const playlist of playlistStore.byFolder(folder)) {
      playables.push(...await songStore.fetchForPlaylist(playlist))
    }

    return uniqBy(playables, 'id')
  },

  async fetchForPodcast (podcast: Podcast | Podcast['id'], refresh = false) {
    const id = typeof podcast === 'string' ? podcast : podcast.id

    if (refresh) {
      cache.remove(['podcast.episodes', id])
    }

    return await cache.remember<Episode[]>(
      [`podcast.episodes`, id],
      async () => this.syncWithVault(await http.get<Episode[]>(`podcasts/${id}/episodes${refresh ? '?refresh=1' : ''}`)),
    )
  },

  async paginateForGenre (genre: Genre | Genre['name'], params: GenreSongListPaginateParams) {
    const name = typeof genre === 'string' ? genre : genre.name

    const resource = await http.get<PaginatorResource<Song>>(
      `genres/${name}/songs?${new URLSearchParams(params).toString()}`,
    )

    const songs = this.syncWithVault(resource.data)

    return {
      songs,
      nextPage: resource.links.next ? ++resource.meta.current_page : null,
    }
  },

  async fetchRandomForGenre (genre: Genre | Genre['name'], limit = 500) {
    const name = typeof genre === 'string' ? genre : genre.name
    return this.syncWithVault(await http.get<Song[]>(`genres/${name}/songs/random?limit=${limit}`))
  },

  async paginate (params: SongListPaginateParams) {
    const resource = await http.get<PaginatorResource<Song>>(`songs?${new URLSearchParams(params).toString()}`)
    this.state.songs = unionBy(this.state.songs, this.syncWithVault(resource.data), 'id')

    return resource.links.next ? ++resource.meta.current_page : null
  },

  getMostPlayed (count: number) {
    return take(
      orderBy(
        Array
          .from(this.vault.values())
          .filter(playable => isSong(playable) && !playable.deleted && playable.play_count > 0),
        'play_count',
        'desc',
      ),
      count,
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
    if (songs.some(song => !isSong(song))) {
      throw new Error('This action is only supported for songs.')
    }

    await http.put('songs/publicize', {
      songs: songs.map(song => song.id),
    })

    songs.forEach(song => (song.is_public = true))
  },

  async privatize (songs: Song[]) {
    if (songs.some(song => !isSong(song))) {
      throw new Error('This action is only supported for songs.')
    }

    const privatizedIds = await http.put<Song['id'][]>('songs/privatize', {
      songs: songs.map(({ id }) => id),
    })

    privatizedIds.forEach(id => {
      const song = this.byId(id) as Song
      song && (song.is_public = false)
    })

    return privatizedIds
  },
}
