import _ from 'lodash';

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

        _.each(this.state.playlists, this.getSongs);
    },

    all() {
        return this.state.playlists;
    },

    /**
     * Get all songs in a playlist.
     *
     * return {Array}
     */
    getSongs(playlist) {
        return (playlist.songs = songStore.byIds(playlist.songs));
    },

    /**
     * Create a new playlist, optionally with its songs.
     * 
     * @param  {string}     name  Name of the playlist
     * @param  {Array}      songs An array of song objects
     * @param  {?Function}  cb
     */
    store(name, songs, cb = null) {
        if (songs.length) {
            // Extract the IDs from the song objects.
            songs = _.pluck(songs, 'id');
        }

        http.post('playlist', { name, songs }, response => {
            var playlist = response.data;
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
        http.delete(`playlist/${playlist.id}`, {}, () => {
            this.state.playlists = _.without(this.state.playlists, playlist);

            if (cb) {
                cb();
            }
        });
    },

    /**
     * Add songs into a playlist.
     * 
     * @param {Object}      playlist
     * @param {Array}       songs
     * @param {?Function}   cb
     */
    addSongs(playlist, songs, cb = null) {
        playlist.songs = _.union(playlist.songs, songs);

        http.put(`playlist/${playlist.id}/sync`, { songs: _.pluck(playlist.songs, 'id') }, () => {
            if (cb) {
                cb();
            }
        });
    },

    /**
     * Remove songs from a playlist.
     * 
     * @param  {Object}     playlist
     * @param  {Array}      songs 
     * @param  {?Function}  cb
     */
    removeSongs(playlist, songs, cb = null) {
        playlist.songs = _.difference(playlist.songs, songs);
        
        http.put(`playlist/${playlist.id}/sync`, { songs: _.pluck(playlist.songs, 'id') }, () => {
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
        http.put(`playlist/${playlist.id}`, { name: playlist.name }, () => {
            if (cb) {
                cb();
            }
        });
    },
};
