import isMobile from 'ismobilejs'
import slugify from 'slugify'
import { merge, orderBy, sumBy, take, unionBy } from 'lodash'
import { reactive, UnwrapNestedRefs, watch } from 'vue'
import { arrayify, eventBus, logger, secondsToHis, use } from '@/utils'
import { authService, cache, httpService } from '@/services'
import { albumStore, artistStore, commonStore, overviewStore, preferenceStore } from '@/stores'

export type SongUpdateData = {
  title?: string
  artist_name?: string
  album_name?: string
  album_artist_name?: string
  track?: number | null
  disc?: number | null
  lyrics?: string
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

export const songStore = {
  vault: new Map<string, UnwrapNestedRefs<Song>>(),

  state: reactive({
    songs: [] as Song[]
  }),

  getFormattedLength: (songs: Song | Song[]) => secondsToHis(sumBy(arrayify(songs), 'length')),

  byId (id: string) {
    return this.vault.get(id)
  },

  byIds (ids: string[]) {
    const songs = [] as Song[]
    ids.forEach(id => use(this.byId(id), song => songs.push(song!)))
    return songs
  },

  byAlbum (album: Album) {
    return Array.from(this.vault.values()).filter(song => song.album_id === album.id)
  },

  async resolve (id: string) {
    let song = this.byId(id)

    if (!song) {
      try {
        song = this.syncWithVault(await httpService.get<Song>(`songs/${id}`))[0]
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
    const interaction = await httpService.post<Interaction>('interaction/play', { song: song.id })

    // Use the data from the server to make sure we don't miss a play from another device.
    song.play_count = interaction.play_count
  },

  scrobble: async (song: Song) => await httpService.post(`songs/${song.id}/scrobble`, {
    timestamp: song.play_start_time
  }),

  async update (songsToUpdate: Song[], data: SongUpdateData) {
    const { songs, artists, albums, removed } = await httpService.put<SongUpdateResult>('songs', {
      data,
      songs: songsToUpdate.map(song => song.id)
    })

    this.syncWithVault(songs)

    albumStore.syncWithVault(albums)
    artistStore.syncWithVault(artists)

    albumStore.removeByIds(removed.albums.map(album => album.id))
    artistStore.removeByIds(removed.artists.map(artist => artist.id))

    eventBus.emit('SONGS_UPDATED')

    overviewStore.refresh()
  },

  getSourceUrl: (song: Song) => {
    return isMobile.any && preferenceStore.transcodeOnMobile
      ? `${commonStore.state.cdn_url}play/${song.id}/1/128?api_token=${authService.getToken()}`
      : `${commonStore.state.cdn_url}play/${song.id}?api_token=${authService.getToken()}`
  },

  getShareableUrl: (song: Song) => `${window.BASE_URL}#!/song/${song.id}`,

  syncWithVault (songs: Song | Song[]) {
    return arrayify(songs).map(song => {
      let local = this.byId(song.id)

      if (local) {
        merge(local, song)
      } else {
        local = reactive(song)
        local.playback_state = 'Stopped'
        this.trackPlayCount(local)
        this.vault.set(local.id, local)
      }

      return local
    })
  },

  trackPlayCount: (song: UnwrapNestedRefs<Song>) => {
    watch(() => song.play_count, (newCount, oldCount) => {
      const album = albumStore.byId(song.album_id)
      album && (album.play_count += (newCount - oldCount))

      const artist = artistStore.byId(song.artist_id)
      artist && (artist.play_count += (newCount - oldCount))

      if (song.album_artist_id !== song.artist_id) {
        const albumArtist = artistStore.byId(song.album_artist_id)
        albumArtist && (albumArtist.play_count += (newCount - oldCount))
      }

      overviewStore.refresh()
    })
  },

  async fetchForAlbum (album: Album | number) {
    const id = typeof album === 'number' ? album : album.id

    return await cache.remember<Song[]>(
      [`album.songs`, id],
      async () => this.syncWithVault(await httpService.get<Song[]>(`albums/${id}/songs`))
    )
  },

  async fetchForArtist (artist: Artist | number) {
    const id = typeof artist === 'number' ? artist : artist.id

    return await cache.remember<Song[]>(
      ['artist.songs', id],
      async () => this.syncWithVault(await httpService.get<Song[]>(`artists/${id}/songs`))
    )
  },

  async fetchForPlaylist (playlist: Playlist) {
    return await cache.remember<Song[]>(
      [`playlist.songs`, playlist.id],
      async () => this.syncWithVault(await httpService.get<Song[]>(`playlists/${playlist.id}/songs`))
    )
  },

  async paginate (sortField: SongListSortField, sortOrder: SortOrder, page: number) {
    const resource = await httpService.get<PaginatorResource>(
      `songs?page=${page}&sort=${sortField}&order=${sortOrder}`
    )

    this.state.songs = unionBy(this.state.songs, this.syncWithVault(resource.data), 'id')

    return resource.links.next ? ++resource.meta.current_page : null
  },

  getMostPlayed (count: number) {
    return take(orderBy(Array.from(this.vault.values()), 'play_count', 'desc'), count)
  },

  getRecentlyAdded (count: number) {
    return take(orderBy(Array.from(this.vault.values()), 'created_at', 'desc'), count)
  }
}
