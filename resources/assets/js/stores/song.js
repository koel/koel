import _ from 'lodash';

import http from '../services/http';
import utils from '../services/utils';
import stub from '../stubs/song';
import favoriteStore from './favorite';
import userStore from './user';

export default {
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
        this.state.songs = _.reduce(albums, (songs, album) => {
            // While doing so, we populate some other information into the songs as well.
            _.each(album.songs, song => {
                song.fmtLength = utils.secondsToHis(song.length);

                // Manually set these addtional properties to be reactive
                Vue.set(song, 'playCount', 0);
                Vue.set(song, 'album', album);
                Vue.set(song, 'liked', false);
                Vue.set(song, 'lyrics', null);
                Vue.set(song, 'playbackState', 'stopped');

                // Cache the song, so that byId() is faster
                this.cache[song.id] = song;
            });

            return songs.concat(album.songs);
        }, []);
    },

    /**
     * Initializes the interaction (like/play count) information.
     *
     * @param  {Array.<Object>} interactions The array of interactions of the current user
     */
    initInteractions(interactions) {
        favoriteStore.clear();

        _.each(interactions, interaction => {
            var song = this.byId(interaction.song_id);

            if (!song) {
                return;
            }

            song.liked = interaction.liked;
            song.playCount = interaction.play_count;
            song.album.playCount += song.playCount;
            song.album.artist.playCount += song.playCount;

            if (song.liked) {
                favoriteStore.add(song);
            }
        });
    },

    /**
     * Get the total duration of some songs.
     *
     * @param {Array.<Object>}  songs
     * @param {Boolean}         toHis Whether to convert the duration into H:i:s format
     *
     * @return {Float|String}
     */
    getLength(songs, toHis) {
        var duration = _.reduce(songs, (length, song) => length + song.length, 0);

        if (toHis) {
            return utils.secondsToHis(duration);
        }

        return duration;
    },

    /**
     * Get all songs.
     *
     * @return {Array.<Object>}
     */
    all() {
        return this.state.songs;
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
        return _.map(ids, id => this.byId(id));
    },

    /**
     * Increase a play count for a song.
     *
     * @param {Object} song
     * @param {?Function} cb
     */
    registerPlay(song, cb = null) {
        var oldCount = song.playCount;

        http.post('interaction/play', { song: song.id }, response => {
            // Use the data from the server to make sure we don't miss a play from another device.
            song.playCount = response.data.play_count;
            song.album.playCount += song.playCount - oldCount;
            song.album.artist.playCount += song.playCount - oldCount;

            if (cb) {
                cb();
            }
        });
    },

    /**
     * Add a song into the "recently played" list.
     *
     * @param {Object}
     */
    addRecent(song) {
        // First we make sure that there's no duplicate.
        this.state.recent = _.without(this.state.recent, song);

        // Then we prepend the song into the list.
        this.state.recent.unshift(song);
    },

    /**
     * Get extra song information (lyrics, artist info, album info).
     *
     * @param  {Object}     song
     * @param  {?Function}  cb
     */
    getInfo(song, cb = null) {
        // Check if the song's info has been retrieved before.
        if (song.lyrics !== null) {
            if (cb) {
                cb();
            }

            return;
        }

        http.get(`${song.id}/info`, data => {
            song.lyrics = data.lyrics;

            // If the artist image is not in a nice form, don't use it.
            if (data.artist_info && typeof data.artist_info.image !== 'string') {
                data.artist_info.image = null;
            }

            song.album.artist.info = data.artist_info;

            // Set the artist image on the client side to the retrieved image from server.
            if (data.artist_info.image) {
                song.album.artist.image = data.artist_info.image;
            }

            // Convert the duration into i:s
            if (data.album_info && data.album_info.tracks) {
                _.each(data.album_info.tracks, track => track.fmtLength = utils.secondsToHis(track.length));
            }

            // If the album cover is not in a nice form, don't use it.
            if (data.album_info && typeof data.album_info.image !== 'string') {
                data.album_info.image = null;
            }

            song.album.info = data.album_info;

            // Set the album on the client side to the retrieved image from server.
            if (data.album_info.cover) {
                song.album.cover = data.album_info.cover;
            }

            if (cb) {
                cb();
            }
        });
    },

    /**
     * Scrobble a song (using Last.fm).
     *
     * @param  {Object}     song
     * @param  {?Function}  cb
     */
    scrobble(song, cb = null) {
        if (!window.useLastfm || !userStore.current().preferences.lastfm_session_key) {
            return;
        }

        http.post(`${song.id}/scrobble/${song.playStartTime}`, () => {
            if (cb) {
                cb();
            }
        });
    },

    /**
     * Get the last n recently played songs.
     *
     * @param  {Number} n
     *
     * @return {Array.<Object>}
     */
    getRecent(n = 10) {
        // And last, make sure the list doesn't exceed 10 items.
        return _.take(this.state.recent, n);
    },

    /**
     * Get top n most-played songs.
     *
     * @param  {Number} n
     *
     * @return {Array.<Object>}
     */
    getMostPlayed(n = 10) {
        var songs = _.take(_.sortByOrder(this.state.songs, 'playCount', 'desc'), n);

        // Remove those with playCount=0
        _.remove(songs, song => !song.playCount);

        return songs;
    },
};
