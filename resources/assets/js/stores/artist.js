/*eslint camelcase: ["error", {properties: "never"}]*/

import Vue from 'vue'
import { reduce, each, union, difference, take, filter, orderBy } from 'lodash'

import config from '../config'
import stub from '../stubs/artist'
import { albumStore } from '.'

const UNKNOWN_ARTIST_ID = 1
const VARIOUS_ARTISTS_ID = 2

export const artistStore = {
  stub,
  cache: [],

  state: {
    artists: []
  },

  /**
   * Init the store.
   *
   * @param  {Array.<Object>} artists The array of artists we got from the server.
   */
  init (artists) {
    this.all = artists

    // Traverse through artists array to get the cover and number of songs for each.
    each(this.all, artist => this.setupArtist(artist))
    albumStore.init(this.all)
  },

  /**
   * Set up the (reactive) properties of an artist.
   *
   * @param  {Object} artist
   */
  setupArtist (artist) {
    this.getImage(artist)
    Vue.set(artist, 'playCount', 0)

    // Here we build a list of songs performed by the artist, so that we don't need to traverse
    // down the "artist > albums > items" route later.
    // This also makes sure songs in compilation albums are counted as well.
    Vue.set(artist, 'songs', reduce(artist.albums, (songs, album) => {
      // If the album is compilation, we cater for the songs contributed by this artist only.
      if (album.is_compilation) {
        return songs.concat(filter(album.songs, { contributing_artist_id: artist.id }))
      }

      // Otherwise, just use all songs in the album.
      return songs.concat(album.songs)
    }, []))

    Vue.set(artist, 'songCount', artist.songs.length)
    Vue.set(artist, 'info', null)
    this.cache[artist.id] = artist

    return artist
  },

  /**
   * Get all artists.
   *
   * @return {Array.<Object>}
   */
  get all () {
    return this.state.artists
  },

  /**
   * Set all artists.
   *
   * @param  {Array.<Object>} value
   */
  set all (value) {
    this.state.artists = value
  },

  /**
   * Get an artist object by its ID.
   *
   * @param  {Number} id
   */
  byId (id) {
    return this.cache[id]
  },

  /**
   * Adds an artist/artists into the current collection.
   *
   * @param  {Array.<Object>|Object} artists
   */
  add (artists) {
    artists = [].concat(artists)
    each(artists, artist => this.setupArtist(artist))

    this.all = union(this.all, artists)
  },

  /**
   * Remove artist(s) from the store.
   *
   * @param  {Array.<Object>|Object} artists
   */
  remove (artists) {
    artists = [].concat(artists)
    this.all = difference(this.all, artists)

    // Remember to clear the cache
    each(artists, artist => delete this.cache[artist.id])
  },

  /**
   * Add album(s) into an artist.
   *
   * @param {Object} artist
   * @param {Array.<Object>|Object} albums
   *
   */
  addAlbumsIntoArtist (artist, albums) {
    albums = [].concat(albums)

    artist.albums = union(artist.albums || [], albums)

    each(albums, album => {
      album.artist_id = artist.id
      album.artist = artist
      artist.playCount += album.playCount
    })
  },

  /**
   * Remove album(s) from an artist.
   *
   * @param  {Object} artist
   * @param  {Array.<Object>|Object} albums
   */
  removeAlbumsFromArtist (artist, albums) {
    albums = [].concat(albums)
    artist.albums = difference(artist.albums, albums)
    each(albums, album => {
      artist.playCount -= album.playCount
    })
  },

  /**
   * Checks if an artist is empty.
   *
   * @param  {Object}  artist
   *
   * @return {boolean}
   */
  isArtistEmpty (artist) {
    return !artist.albums.length
  },

  /**
   * Determine if the artist is the special "Various Artists".
   *
   * @param  {Object}  artist
   *
   * @return {Boolean}
   */
  isVariousArtists (artist) {
    return artist.id === VARIOUS_ARTISTS_ID
  },

  /**
   * Determine if the artist is the special "Unknown Artist".
   *
   * @param  {Object}  artist [description]
   *
   * @return {Boolean}
   */
  isUnknownArtist (artist) {
    return artist.id === UNKNOWN_ARTIST_ID
  },

  /**
   * Get all songs performed by an artist.
   *
   * @param {Object} artist
   *
   * @return {Array.<Object>}
   */
  getSongsByArtist (artist) {
    return artist.songs
  },

  /**
   * Get the artist's image.
   *
   * @param {Object} artist
   *
   * @return {String}
   */
  getImage (artist) {
    if (!artist.image) {
      // Try to get an image from one of the albums.
      artist.image = config.unknownCover

      artist.albums.every(album => {
        // If there's a "real" cover, use it.
        if (album.image !== config.unknownCover) {
          artist.image = album.cover

          // I want to break free.
          return false
        }
      })
    }

    return artist.image
  },

  /**
   * Get top n most-played artists.
   *
   * @param  {Number} n
   *
   * @return {Array.<Object>}
   */
  getMostPlayed (n = 6) {
    // Only non-unknown artists with actually play count are applicable.
    // Also, "Various Artists" doesn't count.
    const applicable = filter(this.all, artist => {
      return artist.playCount &&
        !this.isUnknownArtist(artist) &&
        !this.isVariousArtists(artist)
    })

    return take(orderBy(applicable, 'playCount', 'desc'), n)
  }
}
