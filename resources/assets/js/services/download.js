import { reduce } from 'lodash'

import { playlistStore, favoriteStore } from '@/stores'
import { ls } from '.'

export const download = {
  /**
   * Download individual song(s).
   *
   * @param {Array.<Object>|Object} songs
   */
  fromSongs (songs) {
    songs = [].concat(songs)
    const query = reduce(songs, (q, song) => `songs[]=${song.id}&${q}`, '')
    return this.trigger(`songs?${query}`)
  },

  /**
   * Download all songs in an album.
   *
   * @param {Object} album
   */
  fromAlbum (album) {
    return this.trigger(`album/${album.id}`)
  },

  /**
   * Download all songs performed by an artist.
   *
   * @param {Object} artist
   */
  fromArtist (artist) {
    // It's safe to assume an artist always has songs.
    // After all, what's an artist without her songs?
    // (See what I did there? Yes, I'm advocating for women's rights).
    return this.trigger(`artist/${artist.id}`)
  },

  /**
   * Download all songs in a playlist.
   *
   * @param {Object} playlist
   */
  fromPlaylist (playlist) {
    if (!playlistStore.getSongs(playlist).length) {
      return
    }

    return this.trigger(`playlist/${playlist.id}`)
  },

  /**
   * Download all favorite songs.
   */
  fromFavorites () {
    if (!favoriteStore.all.length) {
      console.warn("You don't like any song? Come on, don't be that grumpy.")
      return
    }

    return this.trigger('favorites')
  },

  /**
   * Build a download link using a segment and trigger it.
   *
   * @param  {string} uri The uri segment, corresponding to the song(s),
   *            artist, playlist, or album.
   */
  trigger (uri) {
    const sep = uri.indexOf('?') === -1 ? '?' : '&'
    const iframe = document.createElement('iframe')
    iframe.style.display = 'none'
    iframe.setAttribute('src', `/api/download/${uri}${sep}jwt-token=${ls.get('jwt-token')}`)
    document.body.appendChild(iframe)
  }
}
