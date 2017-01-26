import Vue from 'vue';
import { reduce, each, find, union, difference, take, filter, orderBy, pluck } from 'lodash';

import config from '../config';
import stub from '../stubs/genre';

const UNKNOWN_GENRE_ID = 1;

export const genreStore = {
  stub,

  state: {
    genres: [stub],
  },

  /**
   * Init the store.
   *
   * @param  {Array.<Object>} genres The array of genres we got from the server.
   */
  init(genres) {
    this.all = genres;
    each(genres, genre => {
        this.setupGenre(genre);
    });
  },

  /**
   * Set up the (reactive) properties of an genre.
   *
   * @param  {Object} genre
   */
  setupGenre(genre) {
    Vue.set(genre, 'playCount', 0);
    // Will be filled later on by while the song store is initializing
    genre.songs = genre.songs ? genre.songs : [];
    Vue.set(genre, 'songCount', genre.songs.length);
    if (!genre.image) genre.image = config.unknownCover;

    return genre;
  },

  /**
   * Get all genres.
   *
   * @return {Array.<Object>}
   */
  get all() {
    return this.state.genres;
  },

  /**
   * Set all genres.
   *
   * @param  {Array.<Object>} value
   */
  set all(value) {
    this.state.genres = value;
  },

  /**
   * Get an genre object by its ID.
   *
   * @param  {Number} id
   */
  byId(id) {
    return find(this.all, { id });
  },

  /**
   * Adds an genre/genres into the current collection.
   *
   * @param  {Array.<Object>|Object} genres
   */
  add(genres) {
    genres = [].concat(genres);
    each(genres, a => this.setupGenre(a));

    this.all = union(this.all, genres);
  },

  /**
   * Remove genre(s) from the store.
   *
   * @param  {Array.<Object>|Object} genres
   */
  remove(genres) {
    this.all = difference(this.all, [].concat(genres));
  },

  /**
   * Add song(s) into a genre.
   *
   * @param {Object} genre
   * @param {Array.<Object>|Object} songs
   *
   */
  addSongsIntoGenre(genre, songs) {
    songs = [].concat(songs);

    genre.songs = union(genre.songs ? genre.songs : [], songs);

    each(songs, song => {
      song.genre_id = genre.id;
      song.genre = genre;
      genre.playCount += song.playCount;
    });
  },

  /**
   * Remove song(s) from a genre.
   *
   * @param  {Object} genre
   * @param  {Array.<Object>|Object} songs
   */
  removeSongsFromGenre(genre, songs) {
    songs = [].concat(songs);
    genre.songs = difference(genre.songs, songs);
    each(songs, song => genre.playCount -= song.playCount);
  },

  /**
   * Checks if an genre is empty.
   *
   * @param  {Object}  genre
   *
   * @return {boolean}
   */
  isGenreEmpty(genre) {
    return !genre.songs.length;
  },

  /**
   * Determine if the genre is the special "Unknown Genre".
   *
   * @param  {Object}  genre [description]
   *
   * @return {Boolean}
   */
  isUnknownGenre(genre) {
    return genre.id === UNKNOWN_GENRE_ID;
  },

  /**
   * Get all songs for this genre.
   *
   * @param {Object} genre
   *
   * @return {Array.<Object>}
   */
  getSongsByGenre(genre) {
    return genre.songs;
  },

  /**
   * Get top n most-played genres.
   *
   * @param  {Number} n
   *
   * @return {Array.<Object>}
   */
  getMostPlayed(n = 6) {
    // Only non-unknown artists with actually play count are applicable.
    // Also, "Various Artists" doesn't count.
    const applicable = filter(this.all, genre => {
      return genre.playCount
        && !this.isUnknownGenre(genre);
    });

    return take(orderBy(applicable, 'playCount', 'desc'), n);
  },
};
