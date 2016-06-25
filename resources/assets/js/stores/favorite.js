import {
    each,
    map,
    difference,
    union
} from 'lodash';

import { http } from '../services';

export const favoriteStore = {
    state: {
        songs: [],
        length: 0,
        fmtLength: '',
    },

    /**
     * All songs favorite'd by the current user.
     *
     * @return {Array.<Object>}
     */
    get all() {
        return this.state.songs;
    },

    /**
     * Set all favorite'd songs.
     *
     * @param  {Array.<Object>} value
     */
    set all(value) {
        this.state.songs = value;
    },

    /**
     * Toggle like/unlike a song.
     * A request to the server will be made.
     *
     * @param {Object}     song
     * @param {?Function}  cb
     */
    toggleOne(song, cb = null) {
        // Don't wait for the HTTP response to update the status, just toggle right away.
        // This may cause a minor problem if the request fails somehow, but do we care?
        song.liked = !song.liked;

        song.liked ? this.add(song) : this.remove(song);

        http.post('interaction/like', { song: song.id }, () => cb && cb());
    },

    /**
     * Add a song/songs into the store.
     *
     * @param {Array.<Object>|Object} songs
     */
    add(songs) {
        this.all = union(this.all, [].concat(songs));
    },

    /**
     * Remove a song/songs from the store.
     *
     * @param {Array.<Object>|Object} songs
     */
    remove(songs) {
        this.all = difference(this.all, [].concat(songs));
    },

    /**
     * Remove all favorites.
     */
    clear() {
        this.all = [];
    },

    /**
     * Like a bunch of songs.
     *
     * @param {Array.<Object>}  songs
     * @param {?Function}       cb
     */
    like(songs, cb = null) {
        // Don't wait for the HTTP response to update the status, just set them to Liked right away.
        // This may cause a minor problem if the request fails somehow, but do we care?
        each(songs, song => song.liked = true);
        this.add(songs);

        http.post('interaction/batch/like', { songs: map(songs, 'id') }, () => cb && cb());
    },

    /**
     * Unlike a bunch of songs.
     *
     * @param {Array.<Object>}  songs
     * @param {?Function}       cb
     */
    unlike(songs, cb = null) {
        each(songs, song => song.liked = false);
        this.remove(songs);

        http.post('interaction/batch/unlike', { songs: map(songs, 'id') }, () => cb && cb());
    },
};
