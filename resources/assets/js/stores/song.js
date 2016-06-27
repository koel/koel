import Vue from 'vue';
import { without, map, take, remove, orderBy, each, union } from 'lodash';

import { secondsToHis } from '../utils';
import { http, ls } from '../services';
import { sharedStore, favoriteStore, userStore, albumStore, artistStore } from '.';
import stub from '../stubs/song';

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
     * The recently played songs **in the current session**
     *
     * @type {Array}
     */
    recent: [],
  },

  /**
   * Init the store.
   *
   * @param  {Array.<Object>} albums The array of albums to extract our songs from
   */
  init(albums) {
    // Iterate through the albums. With each, add its songs into our master song list.
    // While doing so, we populate some other information into the songs as well.
    this.all = albums.reduce((songs, album) => {
      each(album.songs, song => {
        this.setupSong(song, album);
      });

      return songs.concat(album.songs);
    }, []);
  },

  setupSong(song, album) {
    song.fmtLength = secondsToHis(song.length);

    // Manually set these additional properties to be reactive
    Vue.set(song, 'playCount', 0);
    Vue.set(song, 'album', album);
    Vue.set(song, 'liked', false);
    Vue.set(song, 'lyrics', null);
    Vue.set(song, 'playbackState', 'stopped');

    if (song.contributing_artist_id) {
      const artist = artistStore.byId(song.contributing_artist_id);
      artist.albums = union(artist.albums, [album]);
      artistStore.setupArtist(artist);
      Vue.set(song, 'artist', artist);
    } else {
      Vue.set(song, 'artist', artistStore.byId(song.album.artist.id));
    }

    // Cache the song, so that byId() is faster
    this.cache[song.id] = song;
  },

  /**
   * Initializes the interaction (like/play count) information.
   *
   * @param  {Array.<Object>} interactions The array of interactions of the current user
   */
  initInteractions(interactions) {
    favoriteStore.clear();

    each(interactions, interaction => {
      const song = this.byId(interaction.song_id);

      if (!song) {
        return;
      }

      song.liked = interaction.liked;
      song.playCount = interaction.play_count;
      song.album.playCount += song.playCount;
      song.artist.playCount += song.playCount;

      if (song.liked) {
        favoriteStore.add(song);
      }
    });
  },

  /**
   * Get the total duration of some songs.
   *
   * @param {Array.<Object>}  songs
   * @param {Boolean}     toHis Whether to convert the duration into H:i:s format
   *
   * @return {Float|String}
   */
  getLength(songs, toHis) {
    const duration = songs.reduce((length, song) => length + song.length, 0);

    return toHis ? secondsToHis(duration) : duration;
  },

  /**
   * Get all songs.
   *
   * @return {Array.<Object>}
   */
  get all() {
    return this.state.songs;
  },

  /**
   * Set all songs.
   *
   * @param  {Array.<Object>} value
   */
  set all(value) {
    this.state.songs = value;
  },

  /**
   * Get a song by its ID.
   *
   * @param  {String} id
   *
   * @return {Object}
   */
  byId(id) {
    return this.cache[id];
  },

  /**
   * Get songs by their IDs.
   *
   * @param  {Array.<String>} ids
   *
   * @return {Array.<Object>}
   */
  byIds(ids) {
    return ids.map(id => this.byId(id));
  },

  /**
   * Increase a play count for a song.
   *
   * @param {Object} song
   * @param {?Function} cb
   */
  registerPlay(song, cb = null) {
    const oldCount = song.playCount;

    http.post('interaction/play', { song: song.id }, response => {
      // Use the data from the server to make sure we don't miss a play from another device.
      song.playCount = response.data.play_count;
      song.album.playCount += song.playCount - oldCount;
      song.artist.playCount += song.playCount - oldCount;

      cb && cb();
    });
  },

  /**
   * Add a song into the "recently played" list.
   *
   * @param {Object}
   */
  addRecent(song) {
    // First we make sure that there's no duplicate.
    this.state.recent = without(this.state.recent, song);

    // Then we prepend the song into the list.
    this.state.recent.unshift(song);
  },

  /**
   * Scrobble a song (using Last.fm).
   *
   * @param  {Object}   song
   * @param  {?Function}  cb
   */
  scrobble(song, cb = null) {
    if (!sharedStore.state.useLastfm || !userStore.current.preferences.lastfm_session_key) {
      return;
    }

    http.post(`${song.id}/scrobble/${song.playStartTime}`, () => cb && cb());
  },

  /**
   * Update song data.
   *
   * @param  {Array.<Object>} songs   An array of song
   * @param  {Object}     data
   * @param  {?Function}    successCb
   * @param  {?Function}    errorCb
   */
  update(songs, data, successCb = null, errorCb = null) {
    if (!userStore.current.is_admin) {
      return;
    }

    http.put('songs', {
      data,
      songs: map(songs, 'id'),
    }, response => {
      each(response.data, song => this.syncUpdatedSong(song));

      successCb && successCb();
    }, () => errorCb && errorCb());
  },

  /**
   * Sync an updated song into our current library.
   *
   * This is one of the most ugly functions I've written, if not the worst itself.
   * Sorry, future me.
   * Sorry guys.
   * Forgive me.
   *
   * @param  {Object} updatedSong The updated song, with albums and whatnot.
   *
   * @return {?Object}       The updated song.
   */
  syncUpdatedSong(updatedSong) {
    // Cases:
    // 1. Album doesn't change (and then, artist doesn't either)
    // 2. Album changes (note that a new album might have been created) and
    //    2.a. Artist remains the same.
    //    2.b. Artist changes as well. Note that an artist might have been created.

    // Find the original song,
    const originalSong = this.byId(updatedSong.id);

    if (!originalSong) {
      return;
    }

    // and keep track of original album/artist.
    const originalAlbumId = originalSong.album.id;
    const originalArtistId = originalSong.artist.id;

    // First, we update the title, lyrics, and track #
    originalSong.title = updatedSong.title;
    originalSong.lyrics = updatedSong.lyrics;
    originalSong.track = updatedSong.track;

    if (updatedSong.album.id === originalAlbumId) { // case 1
      // Nothing to do
    } else { // case 2
      // First, remove it from its old album
      albumStore.removeSongsFromAlbum(originalSong.album, originalSong);

      const existingAlbum = albumStore.byId(updatedSong.album.id);
      const newAlbumCreated = !existingAlbum;

      if (!newAlbumCreated) {
        // The song changed to an existing album. We now add it to such album.
        albumStore.addSongsIntoAlbum(existingAlbum, originalSong);
      } else {
        // A new album was created. We:
        // - Add the new album into our collection
        // - Add the song into it
        albumStore.addSongsIntoAlbum(updatedSong.album, originalSong);
        albumStore.add(updatedSong.album);
      }

      if (updatedSong.album.artist.id === originalArtistId) { // case 2.a
        // Same artist, but what if the album is new?
        if (newAlbumCreated) {
          artistStore.addAlbumsIntoArtist(artistStore.byId(originalArtistId), updatedSong.album);
        }
      } else { // case 2.b
        // The artist changes.
        const existingArtist = artistStore.byId(updatedSong.album.artist.id);

        if (existingArtist) {
          originalSong.artist = existingArtist;
        } else {
          // New artist created. We:
          // - Add the album into it, because now it MUST BE a new album
          // (there's no "new artist with existing album" in our system).
          // - Add the new artist into our collection
          artistStore.addAlbumsIntoArtist(updatedSong.album.artist, updatedSong.album);
          artistStore.add(updatedSong.album.artist);
          originalSong.artist = updatedSong.album.artist;
        }
      }

      // As a last step, we purify our library of empty albums/artists.
      if (albumStore.isAlbumEmpty(albumStore.byId(originalAlbumId))) {
        albumStore.remove(albumStore.byId(originalAlbumId));
      }

      if (artistStore.isArtistEmpty(artistStore.byId(originalArtistId))) {
        artistStore.remove(artistStore.byId(originalArtistId));
      }

      // Now we make sure the next call to info() get the refreshed, correct info.
      originalSong.infoRetrieved = false;
    }

    return originalSong;
  },

  /**
   * Get a song's playable source URL.
   *
   * @param  {Object} song
   *
   * @return {string} The source URL, with JWT token appended.
   */
  getSourceUrl(song) {
    return `${sharedStore.state.cdnUrl}api/${song.id}/play?jwt-token=${ls.get('jwt-token')}`;
  },

  /**
   * Get the last n recently played songs.
   *
   * @param  {Number} n
   *
   * @return {Array.<Object>}
   */
  getRecent(n = 10) {
    return take(this.state.recent, n);
  },

  /**
   * Get top n most-played songs.
   *
   * @param  {Number} n
   *
   * @return {Array.<Object>}
   */
  getMostPlayed(n = 10) {
    const songs = take(orderBy(this.all, 'playCount', 'desc'), n);

    // Remove those with playCount=0
    remove(songs, song => !song.playCount);

    return songs;
  },

  /**
   * Called when the application is torn down.
   * Reset stuff.
   */
  teardown() {
    this.state.recent = [];
  },
};
