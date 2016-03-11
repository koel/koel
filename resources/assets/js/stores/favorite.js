import _ from 'lodash';

import http from '../services/http';
import utils from '../services/utils';

export default {
    state: {
        songs: [],
        length: 0,
        fmtLength: '',
    },

    /**
     * Get all songs favorited by the current user.
     *
     * @return {Array.<Object>}
     */
    all() {
        return this.state.songs;
    },

    clear() {
        this.state.songs = [];
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

        if (song.liked) {
            this.add(song);
        } else {
            this.remove(song);
        }

        http.post('interaction/like', { song: song.id }, () => {
            if (cb) {
                cb();
            }
        });
    },

    /**
     * Add a song into the store.
     *
     * @param {Object} song
     */
    add(song) {
        this.state.songs.push(song);
    },

    /**
     * Remove a song from the store.
     *
     * @param {Object} song
     */
    remove(song) {
        this.state.songs = _.difference(this.state.songs, [song]);
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
        _.each(songs, song => song.liked = true);
        this.state.songs = _.union(this.state.songs, songs);

        http.post('interaction/batch/like', { songs: _.pluck(songs, 'id') }, () => {
            if (cb) {
                cb();
            }
        });
    },

    /**
     * Unlike a bunch of songs.
     *
     * @param {Array.<Object>}  songs
     * @param {?Function}       cb
     */
    unlike(songs, cb = null) {
        // Don't wait for the HTTP response to update the status, just set them to Unliked right away.
        // This may cause a minor problem if the request fails somehow, but do we care?
        _.each(songs, song => song.liked = false);
        this.state.songs = _.difference(this.state.songs, songs);

        http.post('interaction/batch/unlike', { songs: _.pluck(songs, 'id') }, () => {
            if (cb) {
                cb();
            }
        });
    },
};
