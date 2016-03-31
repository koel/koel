import {
    each,
    pluck,
    difference,
    union
} from 'lodash';
import NProgress from 'nprogress';

import http from '../services/http';
import stub from '../stubs/playlist';
import songStore from './song';

export default {
    stub,

    state: {
        playlists: [],
    },

    init(playlists) {
        this.state.playlists = playlists;

        each(this.state.playlists, this.getSongs);
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
     * Get all songs in a playlist.
     *
     * return {Array.<Object>}
     */
    getSongs(playlist) {
        return (playlist.songs = songStore.byIds(playlist.songs));
    },

    /**
     * Create a new playlist, optionally with its songs.
     *
     * @param  {String}         name  Name of the playlist
     * @param  {Array.<Object>} songs An array of song objects
     * @param  {?Function}      cb
     */
    store(name, songs, cb = null) {
        if (songs.length) {
            // Extract the IDs from the song objects.
            songs = pluck(songs, 'id');
        }

        NProgress.start();

        http.post('playlist', { name, songs }, response => {
            const playlist = response.data;
            playlist.songs = songs;
            this.getSongs(playlist);
            this.state.playlists.push(playlist);

            if (cb) {
                cb();
            }
        });
    },

    /**
     * Delete a playlist.
     *
     * @param  {Object}     playlist
     * @param  {?Function}  cb
     */
    delete(playlist, cb = null) {
        NProgress.start();

        http.delete(`playlist/${playlist.id}`, {}, () => {
            this.state.playlists = without(this.state.playlists, playlist);

            if (cb) {
                cb();
            }
        });
    },

    /**
     * Add songs into a playlist.
     *
     * @param {Object}          playlist
     * @param {Array.<Object>}  songs
     * @param {?Function}       cb
     */
    addSongs(playlist, songs, cb = null) {
        const count = playlist.songs.length;
        playlist.songs = union(playlist.songs, songs);

        if (count === playlist.songs.length) {
            return;
        }

        http.put(`playlist/${playlist.id}/sync`, { songs: pluck(playlist.songs, 'id') }, () => {
            if (cb) {
                cb();
            }
        });
    },

    /**
     * Remove songs from a playlist.
     *
     * @param  {Object}         playlist
     * @param  {Array.<Object>} songs
     * @param  {?Function}      cb
     */
    removeSongs(playlist, songs, cb = null) {
        playlist.songs = difference(playlist.songs, songs);

        http.put(`playlist/${playlist.id}/sync`, { songs: pluck(playlist.songs, 'id') }, () => {
            if (cb) {
                cb();
            }
        });
    },

    /**
     * Update a playlist (just change its name).
     *
     * @param  {Object}     playlist
     * @param  {?Function}  cb
     */
    update(playlist, cb = null) {
        NProgress.start();

        http.put(`playlist/${playlist.id}`, { name: playlist.name }, () => {
            if (cb) {
                cb();
            }
        });
    },
};
