import { reactive } from 'vue'
import slugify from 'slugify'
import { orderBy, remove, take, unionBy, without } from 'lodash'
import isMobile from 'ismobilejs'

import { arrayify, secondsToHis, use } from '@/utils'
import { authService, httpService } from '@/services'
import { albumStore, artistStore, commonStore, favoriteStore, preferenceStore } from '.'
import stub from '@/stubs/song'

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
}

export const songStore = {
  stub,
  cache: {} as { [key: string]: Song },

  state: reactive({
    songs: [] as Song[],
    recentlyPlayed: [] as Song[]
  }),

  init (songs: Song[]) {
    this.all = songs
    this.all.forEach(song => this.setupSong(song))
  },

  setupSong (song: Song) {
    song.fmtLength = secondsToHis(song.length)

    const album = albumStore.byId(song.album_id)!
    const artist = artistStore.byId(song.artist_id)!

    song.playCount = song.playCount || 0
    song.album = album
    song.artist = artist
    song.liked = song.liked || false
    song.lyrics = song.lyrics || ''
    song.playbackState = song.playbackState || 'Stopped'

    artist.songs = unionBy(artist.songs || [], [song], 'id')
    album.songs = unionBy(album.songs || [], [song], 'id')

    // now if the song is part of a compilation album, the album must be added
    // into its artist as well
    if (album.is_compilation) {
      artist.albums = unionBy(artist.albums, [album], 'id')
    }

    // Cache the song, so that byId() is faster
    this.cache[song.id] = song
  },

  /**
   * Initializes the interaction (like/play count) information.
   *
   * @param  {Interaction[]} interactions The array of interactions of the current user
   */
  initInteractions (interactions: Interaction[]) {
    favoriteStore.clear()

    interactions.forEach(interaction => {
      const song = this.byId(interaction.song_id)

      if (!song) {
        return
      }

      song.liked = interaction.liked
      song.playCount = interaction.play_count
      song.album.playCount += song.playCount
      song.artist.playCount += song.playCount

      song.liked && favoriteStore.add(song)
    })
  },

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

  get all () {
    return this.state.songs
  },

  set all (value: Song[]) {
    this.state.songs = value
  },

  byId (id: string) {
    return this.cache[id]
  },

  byIds (ids: string[]) {
    const songs = [] as Song[]
    arrayify(ids).forEach(id => use(this.byId(id), song => songs.push(song!)))
    return songs
  },

  /**
   * Guess a song by its title and album.
   * Forget about Levenshtein distance, this implementation is good enough.
   */
  guess: (title: string, album: Album) => {
    title = slugify(title.toLowerCase())

    for (const song of album.songs) {
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
    const oldCount = song.playCount

    const interaction = await httpService.post<Interaction>('interaction/play', { song: song.id })

    // Use the data from the server to make sure we don't miss a play from another device.
    song.playCount = interaction.play_count
    song.album.playCount += song.playCount - oldCount
    song.artist.playCount += song.playCount - oldCount
  },

  scrobble: async (song: Song) => await httpService.post(`${song.id}/scrobble`, { timestamp: song.playStartTime }),

  async update (songsToUpdate: Song[], data: any) {
    const { songs, artists, albums } = await httpService.put<SongUpdateResult>('songs', {
      data,
      songs: songsToUpdate.map(song => song.id)
    })

    // Add the artist and album into stores if they're new
    artists.forEach(artist => !artistStore.byId(artist.id) && artistStore.add(artist))
    albums.forEach(album => !albumStore.byId(album.id) && albumStore.add(album))

    songs.forEach(song => {
      let originalSong = this.byId(song.id)!

      if (originalSong.album_id !== song.album_id) {
        // album has been changed. Remove the song from its old album.
        originalSong.album.songs = without(originalSong.album.songs, originalSong)
      }

      if (originalSong.artist_id !== song.artist_id) {
        // artist has been changed. Remove the song from its old artist
        originalSong.artist.songs = without(originalSong.artist.songs, originalSong)
      }

      originalSong = Object.assign(originalSong, song)
      // re-setup the song
      this.setupSong(originalSong)
    })

    artistStore.compact()
    albumStore.compact()

    return songs
  },

  getSourceUrl: (song: Song) => {
    return isMobile.any && preferenceStore.transcodeOnMobile
      ? `${commonStore.state.cdnUrl}play/${song.id}/1/128?api_token=${authService.getToken()}`
      : `${commonStore.state.cdnUrl}play/${song.id}?api_token=${authService.getToken()}`
  },

  getShareableUrl: (song: Song) => `${window.BASE_URL}#!/song/${song.id}`,

  get recentlyPlayed () {
    return this.state.recentlyPlayed
  },

  getMostPlayed (n = 10) {
    const songs = take(orderBy(this.all, 'playCount', 'desc'), n)

    // Remove those with playCount=0
    remove(songs, song => !song.playCount)

    return songs
  },

  getRecentlyAdded (n = 10) {
    return take(orderBy(this.all, 'created_at', 'desc'), n)
  },

  generateDataToBroadcast: (song: Song): BroadcastSongData => ({
    song: {
      id: song.id,
      title: song.title,
      liked: song.liked,
      playbackState: song.playbackState || 'Stopped',
      album: {
        id: song.album.id,
        name: song.album.name,
        cover: song.album.cover
      },
      artist: {
        id: song.artist.id,
        name: song.artist.name
      }
    }
  })
}
