import isMobile from 'ismobilejs'
import slugify from 'slugify'
import { orderBy, take, union } from 'lodash'
import { reactive, watch } from 'vue'
import { arrayify, eventBus, secondsToHis, use } from '@/utils'
import { authService, Cache, httpService } from '@/services'
import { albumStore, artistStore, commonStore, overviewStore, preferenceStore } from '@/stores'

interface BroadcastSongData {
  song: {
    id: string
    title: string
    liked: boolean
    playbackState: PlaybackState
    album: {
      id: number
      name: string
      cover: string
    }
    artist: {
      id: number
      name: string
    }
  }
}

interface SongUpdateResult {
  songs: Song[]
  artists: Artist[]
  albums: Album[]
  removed: {
    albums: Pick<Album, 'id' | 'artist_id' | 'name' | 'cover' | 'created_at'>[]
    artists: Pick<Artist, 'id' | 'name' | 'image' | 'created_at'>[]
  }
}

export const songStore = {
  vault: new Map<string, Song>(),

  state: reactive({
    songs: [] as Song[]
  }),

  /**
   * Get the total duration of some songs.
   *
   * @param songs
   * @param {Boolean} formatted Whether to convert the duration into H:i:s format
   */
  getLength: (songs: Song[], formatted: boolean = false) => {
    const duration = songs.reduce((length, song) => length + song.length, 0)

    return formatted ? secondsToHis(duration) : duration
  },

  getFormattedLength (songs: Song[]) {
    return String(this.getLength(songs, true))
  },

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
    if (this.byId(id)) {
      return this.byId(id)
    }

    try {
      return this.syncWithVault(await httpService.get<Song>(`songs/${id}`))[0]
    } catch (e) {
      return null
    }
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

  scrobble: async (song: Song) => await httpService.post(`${song.id}/scrobble`, { timestamp: song.play_start_time }),

  async update (songsToUpdate: Song[], data: any) {
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

  generateDataToBroadcast: (song: Song): BroadcastSongData => ({
    song: {
      id: song.id,
      title: song.title,
      liked: song.liked,
      playbackState: song.playback_state || 'Stopped',
      album: {
        id: song.album_id,
        name: song.album_name,
        cover: song.album_cover
      },
      artist: {
        id: song.artist_id,
        name: song.artist_name
      }
    }
  }),

  syncWithVault (songs: Song | Song[]) {
    return arrayify(songs).map(song => {
      let local = this.byId(song.id)

      if (local) {
        Object.assign(local, song)
      } else {
        song.playback_state = 'Stopped'
        local = reactive(song)
        this.trackPlayCount(local!)
      }

      this.vault.set(song.id, local)
      return local
    })
  },

  trackPlayCount: (song: Song) => {
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

  async fetchForAlbum (album: Album) {
    return await Cache.resolve<Song[]>(
      [`album.songs`, album.id],
      async () => this.syncWithVault(await httpService.get<Song[]>(`albums/${album.id}/songs`))
    )
  },

  async fetchForArtist (artist: Artist) {
    return await Cache.resolve<Song[]>(
      ['artist.songs', artist.id],
      async () => this.syncWithVault(await httpService.get<Song[]>(`artists/${artist.id}/songs`))
    )
  },

  async fetchForPlaylist (playlist: Playlist) {
    return await Cache.resolve<Song[]>(
      [`playlist.songs`, playlist.id],
      async () => this.syncWithVault(await httpService.get<Song[]>(`playlists/${playlist.id}/songs`))
    )
  },

  async fetch (sortField: SongListSortField, sortOrder: SortOrder, page: number) {
    const resource = await httpService.get<PaginatorResource>(
      `songs?page=${page}&sort=${sortField}&order=${sortOrder}`
    )

    this.state.songs = union(this.state.songs, this.syncWithVault(resource.data))

    return resource.links.next ? ++resource.meta.current_page : null
  },

  getMostPlayed (count: number) {
    return take(orderBy(Array.from(this.vault.values()), 'play_count', 'desc'), count)
  },

  getRecentlyAdded (count: number) {
    return take(orderBy(Array.from(this.vault.values()), 'created_at', 'desc'), count)
  }
}
