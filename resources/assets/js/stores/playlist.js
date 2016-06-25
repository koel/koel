import { each, map, difference, union, without } from 'lodash';
import NProgress from 'nprogress';

import stub from '../stubs/playlist';
import { http } from '../services';
import { songStore } from '.';

export const playlistStore = {
  stub,

  state: {
    playlists: [],
  },

  init(playlists) {
    this.all = playlists;
    each(this.all, this.objectifySongs);
  },

  /**
   * All playlists of the current user.
   *
   * @return {Array.<Object>}
   */
  get all() {
    return this.state.playlists;
  },

  /**
   * Set all playlists.
   *
   * @param  {Array.<Object>} value
   */
  set all(value) {
    this.state.playlists = value;
  },

  /**
   * Objectify all songs in the playlist.
   * (Initially, a playlist only contain the song IDs).
   *
   * @param  {Object} playlist
   */
  objectifySongs(playlist) {
    playlist.songs = songStore.byIds(playlist.songs);
  },

  /**
   * Get all songs in a playlist.
   *
   * @param {Object}
   *
   * return {Array.<Object>}
   */
  getSongs(playlist) {
    return playlist.songs;
  },

  /**
   * Add a playlist/playlists into the store.
   *
   * @param {Array.<Object>|Object} playlists
   */
  add(playlists) {
    this.all = union(this.all, [].concat(playlists));
  },

  /**
   * Remove a playlist/playlists from the store.
   *
   * @param  {Array.<Object>|Object} playlist
   */
  remove(playlists) {
    this.all = difference(this.all, [].concat(playlists));
  },

  /**
   * Create a new playlist, optionally with its songs.
   *
   * @param  {String}     name  Name of the playlist
   * @param  {Array.<Object>} songs An array of song objects
   * @param  {?Function}    cb
   */
  store(name, songs, cb = null) {
    if (songs.length) {
      // Extract the IDs from the song objects.
      songs = map(songs, 'id');
    }

    NProgress.start();

    http.post('playlist', { name, songs }, response => {
      const playlist = response.data;
      playlist.songs = songs;
      this.objectifySongs(playlist);
      this.add(playlist);

      cb && cb();
    });
  },

  /**
   * Delete a playlist.
   *
   * @param  {Object}   playlist
   * @param  {?Function}  cb
   */
  delete(playlist, cb = null) {
    NProgress.start();

    http.delete(`playlist/${playlist.id}`, {}, () => {
      this.remove(playlist);
      cb && cb();
    });
  },

  /**
   * Add songs into a playlist.
   *
   * @param {Object}      playlist
   * @param {Array.<Object>}  songs
   * @param {?Function}     cb
   */
  addSongs(playlist, songs, cb = null) {
    const count = playlist.songs.length;
    playlist.songs = union(playlist.songs, songs);

    if (count === playlist.songs.length) {
      return;
    }

    http.put(`playlist/${playlist.id}/sync`, { songs: map(playlist.songs, 'id') }, () => cb && cb());
  },

  /**
   * Remove songs from a playlist.
   *
   * @param  {Object}     playlist
   * @param  {Array.<Object>} songs
   * @param  {?Function}    cb
   */
  removeSongs(playlist, songs, cb = null) {
    playlist.songs = difference(playlist.songs, songs);

    http.put(`playlist/${playlist.id}/sync`, { songs: map(playlist.songs, 'id') }, () => cb && cb());
  },

  /**
   * Update a playlist (just change its name).
   *
   * @param  {Object}   playlist
   * @param  {?Function}  cb
   */
  update(playlist, cb = null) {
    NProgress.start();

    http.put(`playlist/${playlist.id}`, { name: playlist.name }, () => cb && cb());
  },
};
