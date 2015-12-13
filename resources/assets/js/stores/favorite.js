import _ from 'lodash';

import http from '../services/http';
import utils from '../services/utils';

export default {
    state: {
        songs: [],
    },
    
    all() {
        return this.state.songs;
    },

    /**
     * Toggle like/unlike a song. 
     * A request to the server will be made.
     *
     * @param object        The song object
     * @param closure|null  The function to execute afterwards
     */
    toggleOne(song, cb = null) {
        http.post('interaction/like', { id: song.id }, data => {
            song.liked = data.liked;

            if (data.liked) {
                this.add(song);
            } else {
                this.remove(song);
            }

            if (cb) {
                cb();
            }
        });
    },

    /**
     * Add a song into the store.
     *
     * @param object The song object
     */
    add(song) {
        this.state.songs.push(song);
    },

    /**
     * Remove a song from the store.
     *
     * @param object The song object
     */
    remove(song) {
        this.state.songs = _.difference(this.state.songs, [song]);
    },

    /**
     * Like a bunch of songs.
     * 
     * @param  array An array of songs to like
     */
    like(songs, cb = null) {
        this.state.songs = _.union(this.state.songs, songs);

        http.post('interaction/batch/like', { ids: _.pluck(songs, 'id') }, data => {
            _.each(songs, song => {
                song.liked = true;
            });

            if (cb) {
                cb();
            }
        });
    },

    /**
     * Unlike a bunch of songs.
     * 
     * @param  array An array of songs to unlike
     */
    unlike(songs, cb = null) {
        this.state.songs = _.difference(this.state.songs, songs);

        http.post('interaction/batch/unlike', { ids: _.pluck(songs, 'id') }, data => {
            _.each(songs, song => {
                song.liked = false;
            });

            if (cb) {
                cb();
            }
        });
    },
};
