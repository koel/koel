import isMobile from 'ismobilejs'
import slugify from 'slugify'
import { differenceBy, merge, orderBy, sumBy, take, unionBy, uniqBy } from 'lodash'
import type { Reactive } from 'vue'
import { reactive, watch } from 'vue'
import { arrayify, use } from '@/utils/helpers'
import { isSong } from '@/utils/typeGuards'
import { logger } from '@/utils/logger'
import { md5 } from '@/utils/crypto'
import { secondsToHumanReadable } from '@/utils/formatters'
import { authService } from '@/services/authService'
import { cache } from '@/services/cache'
import { http } from '@/services/http'
import { preferenceStore } from '@/stores/preferenceStore'
import { commonStore } from '@/stores/commonStore'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { overviewStore } from '@/stores/overviewStore'
import { playlistStore } from '@/stores/playlistStore'

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
    album_ids: Album['id'][]
    artist_ids: Artist['id'][]
  }
}

export interface SongListPaginateParams extends Record<string, any> {
  sort: MaybeArray<PlayableListSortField>
  order: SortOrder
  page: number
}

export interface GenreSongListPaginateParams extends Record<string, any> {
  sort: MaybeArray<PlayableListSortField>
  order: SortOrder
  page: number
}

export const playableStore = {
  vault: new Map<Playable['id'], Playable>(),

  state: reactive<{ playables: Playable[], favorites: Playable[] }>({
    playables: [],
    favorites: [],
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

  byIds<T extends Playable = Playable> (ids: T['id'][]) {
    const playables: Playable[] = []
    ids.forEach(id => use(this.byId(id), song => playables.push(song!)))
    return playables as T[]
  },

  byAlbum (album: Album) {
    return Array.from(this.vault.values())
      .filter(playable => isSong(playable) && playable.album_id === album.id) as Song[]
  },

  syncAlbumProperties (album: Album) {
    this.byAlbum(album).forEach(a => {
      a.album_cover = album.cover
      a.album_name = album.name
    })
  },

  byArtist (artist: Artist) {
    return Array.from(this.vault.values())
      .filter(playable => isSong(playable) && playable.artist_id === artist.id) as Song[]
  },

  byAlbumArtist (artist: Artist) {
    return Array.from(this.vault.values())
      .filter(playable => isSong(playable) && playable.album_artist_id === artist.id) as Song[]
  },

  syncArtistProperties (artist: Artist) {
    this.byArtist(artist).forEach(a => {
      a.artist_name = artist.name
    })

    this.byAlbumArtist(artist).forEach(a => {
      a.album_artist_name = artist.name
    })
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

  matchSongsByTitle: (title: string, songs: Song[]) => {
    title = slugify(title.toLowerCase())

    for (const song of songs) {
      if (slugify(song.title.toLowerCase()) === title) {
        return song
      }
    }

    return null
  },

  /**
   * Increase the play count for a playable.
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

  async updateSongs (songsToUpdate: Song[], data: SongUpdateData) {
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

    albumStore.removeByIds(result.removed.album_ids)
    artistStore.removeByIds(result.removed.artist_ids)

    return result
  },

  getSourceUrl: (playable: Playable) => {
    return isMobile.any && preferenceStore.transcode_on_mobile
      ? `${commonStore.state.cdn_url}play/${playable.id}/1?t=${authService.getAudioToken()}`
      : `${commonStore.state.cdn_url}play/${playable.id}?t=${authService.getAudioToken()}`
  },

  getShareableUrl: (song: Playable) => `${window.BASE_URL}#/songs/${song.id}`,

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

  async fetchSongsForAlbum (album: Album | Album['id']) {
    const id = typeof album === 'string' ? album : album.id

    return this.ensureNotDeleted(await cache.remember(
      [`album.songs`, id],
      async () => this.syncWithVault(await http.get<Song[]>(`albums/${id}/songs`)),
    ) as Song[])
  },

  async fetchSongsForArtist (artist: Artist | Artist['id']) {
    const id = typeof artist === 'string' ? artist : artist.id

    return this.ensureNotDeleted(await cache.remember(
      [`artist.songs`, id],
      async () => this.syncWithVault(await http.get<Song[]>(`artists/${id}/songs`)),
    ) as Song[])
  },

  async fetchForPlaylist (playlist: Playlist | Playlist['id'], refresh = false) {
    const id = typeof playlist === 'string' ? playlist : playlist.id

    if (refresh) {
      cache.remove(['playlist.songs', id])
    }

    const songs = this.ensureNotDeleted(await cache.remember(
      [`playlist.songs`, id],
      async () => this.syncWithVault(await http.get<Song[]>(`playlists/${id}/songs`)),
    ) as Song[])

    playlistStore.byId(id)!.playables = songs

    return songs
  },

  async fetchForPlaylistFolder (folder: PlaylistFolder) {
    const playables: Playable[] = []

    for await (const playlist of playlistStore.byFolder(folder)) {
      playables.push(...await this.fetchForPlaylist(playlist))
    }

    return uniqBy(playables, 'id')
  },

  async fetchEpisodesInPodcast (podcast: Podcast | Podcast['id'], refresh = false) {
    const id = typeof podcast === 'string' ? podcast : podcast.id

    if (refresh) {
      cache.remove(['podcast.episodes', id])
    }

    return await cache.remember(
      [`podcast.episodes`, id],
      async () => this.syncWithVault(
        await http.get<Episode[]>(`podcasts/${id}/episodes${refresh ? '?refresh=true' : ''}`),
      ) as Episode[],
    )
  },

  async paginateSongsByGenre (genre: Genre | Genre['id'], params: GenreSongListPaginateParams) {
    const id = typeof genre === 'string' ? genre : genre.id

    const resource = await http.get<PaginatorResource<Song>>(
      `genres/${id}/songs?${new URLSearchParams(params).toString()}`,
    )

    const songs = this.syncWithVault(resource.data) as Song[]

    return {
      songs,
      nextPage: resource.links.next ? ++resource.meta.current_page : null,
    }
  },

  async fetchSongsByGenre (genre: Genre | Genre['id'], random = false, limit = 500) {
    const id = typeof genre === 'string' ? genre : genre.id

    const params = new URLSearchParams({
      limit: String(limit),
      random: String(random),
    }).toString()

    return this.syncWithVault(await http.get<Song[]>(`genres/${id}/songs/queue?${params}`))
  },

  async paginateSongs (params: SongListPaginateParams) {
    const resource = await http.get<PaginatorResource<Playable>>(`songs?${new URLSearchParams(params).toString()}`)
    this.state.playables = unionBy(this.state.playables, this.syncWithVault(resource.data), 'id')

    return resource.links.next ? ++resource.meta.current_page : null
  },

  getMostPlayedSongs (count: number) {
    return take(
      orderBy(
        Array
          .from(this.vault.values())
          .filter(playable => isSong(playable) && !playable.deleted && playable.play_count > 0),
        'play_count',
        'desc',
      ),
      count,
    ) as Song[]
  },

  async deleteSongsFromFilesystem (songs: Song[]) {
    const ids = songs.map(song => {
      // Whenever a vault sync is requested (e.g., upon playlist/album/artist fetching)
      // songs marked as "deleted" will be excluded.
      song.deleted = true
      return song.id
    })

    await http.delete('songs', { songs: ids })
  },

  async publicizeSongs (songs: Song[]) {
    if (songs.some(song => !isSong(song))) {
      throw new Error('This action is only supported for songs.')
    }

    await http.put('songs/publicize', {
      songs: songs.map(song => song.id),
    })

    songs.forEach(song => (song.is_public = true))
  },

  async privatizeSongs (songs: Song[]) {
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

  async resolveSongsFromMediaReferences (data: MediaReference[], shuffle = false) {
    const songReferences = data.filter(item => item.type === 'songs') as Array<Pick<Song, 'type' | 'id'>>
    const songs = this.byIds(songReferences.map(item => item.id)) as Song[]

    const folderReferences = data.filter(item => item.type === 'folders') as Array<Pick<Folder, 'type' | 'path'>>

    if (!folderReferences.length) {
      return songs
    }

    const paths = folderReferences.map(item => item.path).sort()

    // since paths can be long, we use a hash instead
    const cacheKey = ['folders', md5(paths.join(''))]

    const fetcher = () => http.post<Song[]>(`songs/by-folders?shuffle=${shuffle}`, { paths })

    const songsFromFolders = this.syncWithVault(
      shuffle ? await fetcher() : await cache.remember(cacheKey, async () => await fetcher()),
    )

    return unionBy(songs, songsFromFolders as Song[], 'id')
  },

  async fetchSongsInFolder (path: Folder['path']) {
    return this.syncWithVault(await http.get<Song[]>(`songs/in-folder?path=${path}`))
  },

  async fetchFavorites () {
    this.state.favorites = this.syncWithVault(await http.get<Playable[]>('songs/favorite'))
    return this.state.favorites
  },

  async toggleFavorite (playable: Reactive<Playable>) {
    // Don't wait for the HTTP response to update the status, just toggle right away.
    // We'll update the liked status again after the HTTP request.
    playable.favorite = !playable.favorite

    const favorite = await http.post<Favorite | null>(`favorites/toggle`, {
      type: 'playable',
      id: playable.id,
    })

    playable.favorite = Boolean(favorite)

    this.state.favorites = playable.favorite
      ? unionBy(this.state.favorites, arrayify(playable), 'id')
      : differenceBy(this.state.favorites, arrayify(playable), 'id')
  },

  async favorite (playables: MaybeArray<Playable>) {
    playables = arrayify(playables)
    playables.forEach(playable => (playable.favorite = true))

    await http.post('favorites', {
      type: 'playable',
      ids: playables.map(playable => playable.id),
    })

    this.state.favorites = unionBy(this.state.favorites, playables, 'id')
  },

  async undoFavorite (playables: MaybeArray<Playable>) {
    playables = arrayify(playables)
    playables.forEach(playable => (playable.favorite = true))

    await http.delete('favorites', {
      type: 'playable',
      ids: playables.map(playable => playable.id),
    })

    this.state.favorites = differenceBy(this.state.favorites, playables, 'id')
  },
}
