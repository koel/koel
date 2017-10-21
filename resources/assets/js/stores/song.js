import Vue from 'vue'
import slugify from 'slugify'
import { assign, without, map, take, remove, orderBy, each, unionBy, compact } from 'lodash'
import isMobile from 'ismobilejs'

import { secondsToHis, alerts, pluralize } from '@/utils'
import { http, ls } from '@/services'
import { sharedStore, favoriteStore, albumStore, artistStore, preferenceStore } from '.'
import stub from '@/stubs/song'

export const songStore = {
  stub,
  albums: [],
  cache: {},

  state: {
    /**
     * All songs in the store
     *
     * @type {Array}
     */
    songs: [stub],

    /**
     * The recently played songs **on the current machine**
     *
     * @type {Array}
     */
    recentlyPlayed: []
  },

  /**
   * Init the store.
   *
   * @param  {Array.<Object>} songs The array of song objects
   */
  init (songs) {
    this.all = songs
    each(this.all, song => this.setupSong(song))
    this.state.recentlyPlayed = this.gatherRecentlyPlayedFromLocalStorage()
  },

  setupSong (song) {
    song.fmtLength = secondsToHis(song.length)

    const album = albumStore.byId(song.album_id)
    const artist = artistStore.byId(song.artist_id)

    // Manually set these additional properties to be reactive
    Vue.set(song, 'playCount', song.playCount || 0)
    Vue.set(song, 'album', album)
    Vue.set(song, 'artist', artist)
    Vue.set(song, 'liked', song.liked || false)
    Vue.set(song, 'lyrics', song.lyrics || null)
    Vue.set(song, 'playbackState', song.playbackState || 'stopped')

    artist.songs = unionBy(artist.songs || [], [song], 'id')
    album.songs = unionBy(album.songs || [], [song], 'id')

    // now if the song is part of a compilation album, the album must be added
    // into its artist as well
    if (album.is_compilation) {
      artist.albums = unionBy(artist.albums, [album], 'id')
    }

    // Cache the song, so that byId() is faster
    this.cache[song.id] = song

    return song
  },

  /**
   * Initializes the interaction (like/play count) information.
   *
   * @param  {Array.<Object>} interactions The array of interactions of the current user
   */
  initInteractions (interactions) {
    favoriteStore.clear()

    each(interactions, interaction => {
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
   * @param {Array.<Object>}  songs
   * @param {Boolean}     toHis Whether to convert the duration into H:i:s format
   *
   * @return {Float|String}
   */
  getLength (songs, toHis) {
    const duration = songs.reduce((length, song) => length + song.length, 0)

    return toHis ? secondsToHis(duration) : duration
  },

  /**
   * Get all songs.
   *
   * @return {Array.<Object>}
   */
  get all () {
    return this.state.songs
  },

  /**
   * Set all songs.
   *
   * @param  {Array.<Object>} value
   */
  set all (value) {
    this.state.songs = value
  },

  /**
   * Get a song by its ID.
   *
   * @param  {String} id
   *
   * @return {Object}
   */
  byId (id) {
    return this.cache[id]
  },

  /**
   * Get songs by their IDs.
   *
   * @param  {Array.<String>} ids
   *
   * @return {Array.<Object>}
   */
  byIds (ids) {
    return ids.map(id => this.byId(id))
  },

  /**
   * Guess a song by its title and album.
   * Forget about Levenshtein distance, this implementation is good enough.
   *
   * @param  {string} title
   * @param  {Object} album
   *
   * @return {Object|false}
   */
  guess (title, album) {
    title = slugify(title.toLowerCase())
    let found = false
    each(album.songs, song => {
      if (slugify(song.title.toLowerCase()) === title) {
        found = song
      }
    })

    return found
  },

  /**
   * Increase a play count for a song.
   *
   * @param {Object} song
   */
  registerPlay (song) {
    return new Promise((resolve, reject) => {
      const oldCount = song.playCount

      http.post('interaction/play', { song: song.id }, ({ data }) => {
        // Use the data from the server to make sure we don't miss a play from another device.
        song.playCount = data.play_count
        song.album.playCount += song.playCount - oldCount
        song.artist.playCount += song.playCount - oldCount

        resolve(data)
      }, error => reject(error))
    })
  },

  /**
   * Add a song into the "recently played" list.
   *
   * @param {Object} song
   */
  addRecentlyPlayed (song) {
    remove(this.state.recentlyPlayed, s => s.id === song.id)

    // Then we prepend the song into the list.
    this.state.recentlyPlayed.unshift(song)
    // Only take first 7 songs
    this.state.recentlyPlayed.splice(7)
    // Save to local storage as well
    preferenceStore.set('recent-songs', map(this.state.recentlyPlayed, 'id'))
  },

  /**
   * Scrobble a song (using Last.fm).
   *
   * @param  {Object}   song
   */
  scrobble (song) {
    return new Promise((resolve, reject) => {
      http.post(`${song.id}/scrobble/${song.playStartTime}`, {}, ({ data }) => {
        resolve(data)
      }, error => reject(error))
    })
  },

  /**
   * Update song data.
   *
   * @param  {Array.<Object>} songs   An array of song
   * @param  {Object}     data
   */
  update (songs, data) {
    return new Promise((resolve, reject) => {
      http.put('songs', {
        data,
        songs: map(songs, 'id')
      }, ({ data: { songs, artists, albums }}) => {
        // Add the artist and album into stores if they're new
        each(artists, artist => !artistStore.byId(artist.id) && artistStore.add(artist))
        each(albums, album => !albumStore.byId(album.id) && albumStore.add(album))

        each(songs, song => {
          const originalSong = this.byId(song.id)

          if (originalSong.album_id !== song.album_id) {
            // album has been changed. Remove the song from its old album.
            originalSong.album.songs = without(originalSong.album.songs, originalSong)
          }

          if (originalSong.artist_id !== song.artist_id) {
            // artist has been changed. Remove the song from its old artist
            originalSong.artist.songs = without(originalSong.artist.songs, originalSong)
          }

          assign(originalSong, song)
          // re-setup the song
          this.setupSong(originalSong)
        })

        artistStore.compact()
        albumStore.compact()

        alerts.success(`Updated ${pluralize(songs.length, 'song')}.`)
        resolve(songs)
      }, error => reject(error))
    })
  },

  /**
   * Get a song's playable source URL.
   *
   * @param  {Object} song
   *
   * @return {string} The source URL, with JWT token appended.
   */
  getSourceUrl (song) {
    if (isMobile.any && preferenceStore.transcodeOnMobile) {
      return `${sharedStore.state.cdnUrl}api/${song.id}/play/1/128?jwt-token=${ls.get('jwt-token')}`
    }
    return `${sharedStore.state.cdnUrl}api/${song.id}/play?jwt-token=${ls.get('jwt-token')}`
  },

  /**
   * Get a song's shareable URL.
   * Visiting this URL will automatically queue the song and play it.
   *
   * @param  {Object} song
   *
   * @return {string}
   */
  getShareableUrl (song) {
    return `${window.location.origin}/#!/song/${song.id}`
  },

  /**
   * The recently played songs.
   * @return {Array.<Object>}
   */
  get recentlyPlayed () {
    return this.state.recentlyPlayed
  },

  /**
   * Gather the recently played songs from local storage.
   * @return {Array.<Object>}
   */
  gatherRecentlyPlayedFromLocalStorage () {
    return compact(this.byIds(preferenceStore.get('recent-songs') || []))
  },

  /**
   * Get top n most-played songs.
   *
   * @param  {Number} n
   *
   * @return {Array.<Object>}
   */
  getMostPlayed (n = 10) {
    const songs = take(orderBy(this.all, 'playCount', 'desc'), n)

    // Remove those with playCount=0
    remove(songs, song => !song.playCount)

    return songs
  },

  /**
   * Get n most recently added songs.
   * @param  {Number} n
   * @return {Array.<Object>}
   */
  getRecentlyAdded (n = 10) {
    return take(orderBy(this.all, 'created_at', 'desc'), n)
  },

  /**
   * Generate simplified song data to broadcast.
   * @param  {Object} song
   * @return {Object}
   */
  generateDataToBroadcast (song) {
    return {
      song: {
        id: song.id,
        title: song.title,
        liked: song.liked,
        playbackState: song.playbackState,
        album: {
          name: song.album.name,
          cover: song.album.cover
        },
        artist: {
          name: song.artist.name
        }
      }
    }
  }
}
