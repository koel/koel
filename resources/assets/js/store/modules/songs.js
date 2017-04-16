import Vue from 'vue'
import slugify from 'slugify'
import { without, map, take, remove, orderBy, each, union, compact } from 'lodash'
import isMobile from 'ismobilejs'

import { secondsToHis, alerts, pluralize } from '../../utils'
import { http, ls } from '../../services'
import * as types from '../mutation-types'

const cache = {}

const state = {
  songs: [],
  recentlyPlayed: [],
}

const getters = {
  allSongs: state => state.songs,

  songById: state => id => cache[id],

  songsByIds: (state, getters) => ids => ids.map(id => getters.songById(id)),

  guessSong: state => (title, album) => {
    title = slugify(title.toLowerCase())
    let found = false
    each(album.songs, song => {
      if (slugify(song.title.toLowerCase()) === title) {
        found = song
      }
    })

    return found
  },

  recentlyPlayedSongs: state => state.recentlyPlayed,

  songShareableUrl: state => song => `${window.location.origin}/#!/song/${song.id}`,

  mostPlayedSongs: state => (n = 10) => {
    const songs = take(orderBy(state.songs, 'playCount', 'desc'), n)

    // Remove those with playCount=0
    remove(songs, song => !song.playCount)

    return songs
  },

  recentlyAddedSongs: state => (n = 10) => take(orderBy(state.songs, 'created_at', 'desc'), n)
}

const actions = {
  initSongs ({ dispatch, commit, state }, albums) {
    return new Promise(resolve => {
      const songs = albums.reduce((songs, album) => {
        each(album.songs, song => dispatch('setupSong', { song, album }))
        return songs.concat(album.songs)
      }, [])

      commit(types.SONG_INIT_STORE, songs)
      resolve(state.songs)
    })
  },

  setupSong({ commit, getters, dispatch }, { song, album }) {
    return new Promise(resolve => {
      song.fmtLength = secondsToHis(song.length)
      song.playCount = 0
      song.album = album
      song.liked = false
      song.lyrics = null
      song.playbackState = 'stopped'

      if (song.contributing_artist_id) {
        const artist = getters.artistById(song.contributing_artist_id)
        dispatch('addAlbumsIntoArtist', { artist, album })
        song.artist = artist
      } else {
        song.artist = getters.artistById(song.album.artist.id)
      }

      // Cache the song, so that byId() is faster
      cache[song.id] = song
    })
  },

  initInteractions({ commit, state, getters, dispatch }, { interactions }) {
    return new Promise(resolve => {
      each(interactions, interaction => {
        const song = getters.songById(interaction.song_id)

        if (!song) {
          return
        }

        commit(types.SET_INTERACTION_DATA, {
          song,
          liked: interaction.liked,
          playCount: interaction.play_count
        })

        if (song.liked) {
          dispatch('addToFavorites', song)
        }
      })
      resolve()
    })
  },

  registerPlay ({ commit }, song) {

  },

  addRecentlyPlayed ({ commit }, song) {

  },

  scrobble ({ commit }, song) {

  },

  updateSongs ({ commit }, { song, data }) {

  },

  syncUpdatedSong ({ commit }, updatedSong) {

  }
}

const mutations = {
  [types.SONG_INIT_STORE] (state, songs) {
    state.songs = songs
  },

  [types.SET_INTERACTION_DATA] (state, { song, liked, playCount }) {
    song.liked = liked
    song.playCount = playCount
  }
}

export default {
  state,
  getters,
  actions,
  mutations
}
