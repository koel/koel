/*eslint camelcase: ["error", {properties: "never"}]*/

import Vue from 'vue'
import { reduce, each, clone, union, difference, take, filter, orderBy } from 'lodash'

import config from '../../config'
import * as types from '../mutation-types'

const UNKNOWN_ARTIST_ID = 1
const VARIOUS_ARTISTS_ID = 2
const cache = []

const state = {
  artists: []
}

const getters = {
  allArtists: state => state.artists,
  artistById: state => id => cache[id],
  isEmptyArtist: state => artist => !artist.albums.length,
  isVariousArtist: state => artist => artist.id === VARIOUS_ARTISTS_ID,
  isUnknownArtist: state => artist => artist.id === UNKNOWN_ARTIST_ID,
  songsByArtist: state => artist => artist.songs,
  artistImage: state => artist => {

  },
  mostPlayedArtists: state => (n = 6) => {

  }
}

const actions = {
  initArtists ({ dispatch, commit, state }, { artists }) {
    return new Promise(resolve => {
      each(artists, artist => dispatch('setupArtist', artist))

      commit(types.ARTIST_INIT_STORE, artists)
      resolve(state.artists)
    })
  },

  setupArtist ({ commit }, artist) {
    return new Promise(resolve => {
      artist.playCount = 0
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

      // Here we build a list of songs performed by the artist, so that we don't need to traverse
      // down the "artist > albums > items" route later.
      // This also makes sure songs in compilation albums are counted as well.
      artist.songs = reduce(artist.albums, (songs, album) => {
        // If the album is compilation, we cater for the songs contributed by this artist only.
        if (album.is_compilation) {
          return songs.concat(filter(album.songs, { contributing_artist_id: artist.id }))
        }

        // Otherwise, just use all songs in the album.
        return songs.concat(album.songs)
      }, [])

      artist.songCount = artist.songs.length
      artist.info = null
      cache[artist.id] = artist

      resolve(artist)
    })
  },

  addAlbumsIntoArtist ({ commit }, { artist, albums }) {
    commit(types.ARTIST_ADD_ALBUMS_INTO_ARTIST, { artist, albums })
  }
}

const mutations = {
  [types.ARTIST_INIT_STORE] (state, artists) {
    state.artists = artists
  },

  [types.ARTIST_ADD] (state, { artists }) {

  },

  [types.ARTIST_REMOVE] (state, { artists }) {

  },

  [types.ARTIST_ADD_ALBUMS_INTO_ARTIST] (state, { artist, albums }) {
    each(albums, album => {
      album.artist_id = artist.id
      album.artist = artist
      artist.playCount += album.playCount
    })

    artist.albums = union(artist.albums, [].concat(albums))
  },

  [types.ARTIST_REMOVE_ALBUMS_FROM_ARTIST] (state, { artist, albums }) {

  },

  [types.SET_INTERACTION_DATA] (state, { song: { artist }, playCount }) {
    artist.playCount += playCount
  }
}

export default {
  state,
  getters,
  actions,
  mutations
}
