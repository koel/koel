import _ from 'lodash';

import http from '../services/http';
import utils from '../services/utils';
import stub from '../stubs/song';
import albumStore from './album';
import favoriteStore from './favorite';
import sharedStore from './shared';
import userStore from './user';

export default {
    stub,
    sharedStore: null,
    albums: [],

    state: {
        songs: [stub],
        interactions: [],
    },

    /**
     * Init the store.
     * 
     * @param  array albums         The array of albums to extract our songs from
     * @param  array interactions   The array of interactions (like/play count) of the current user
     */
    init(albums, interactions = null) {
        this.albums = albums;

        this.state.interactions = interactions ? interactions : sharedStore.state.interactions;

        // Iterate through the albums. With each, add its songs into our master song list.
        this.state.songs = _.reduce(albums, (songs, album) => {
            // While doing so, we populate some other information into the songs as well.
            _.each(album.songs, song => {
                song.fmtLength = utils.secondsToHis(song.length);

                // Keep a back reference to the album
                song.album = album;
                
                this.setInteractionStats(song);

                if (song.liked) {
                    favoriteStore.add(song);
                }
            });
            
            return songs.concat(album.songs);
        }, []);
    },

    /**
     * Get all songs.
     */
    all() {
        return this.state.songs;
    },

    /**
     * Get a song by its ID
     * 
     * @param  string id
     * 
     * @return object
     */
    byId(id) {
        return _.find(this.state.songs, {id});
    },

    /**
     * Get songs by their ID's
     * 
     * @param  array ids
     * 
     * @return array
     */
    byIds(ids) {
        return _.filter(this.state.songs, song => _.contains(ids, song.id));
    },

    /**
     * Set the interaction stats (like status and playcount) for a song.
     *
     * @param object song
     */
    setInteractionStats(song) {
        var interaction = _.find(this.state.interactions, { song_id: song.id });

        if (!interaction) {
            song.liked = false;
            song.playCount = 0;

            return;
        }

        song.liked = interaction.liked;
        song.playCount = interaction.play_count;
    },

    /**
     * Increase a play count for a song.
     * 
     * @param  object song
     */
    registerPlay(song) {
        // Increase playcount
        http.post('interaction/play', { id: song.id }, data => song.playCount = data.play_count);
    },

    /**
     * Get extra song information (lyrics, artist info, album info).
     * 
     * @param  {Object} song 
     * @param  {Function} cb 
     * 
     * @return {Object}
     */
    getInfo(song, cb = null) {
        if (!_.isUndefined(song.lyrics)) {
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

            // Convert the duration into i:s
            if (data.album_info && data.album_info.tracks) {
                _.each(data.album_info.tracks, track => track.fmtLength = utils.secondsToHis(track.length));
            }

            // If the album cover is not in a nice form, don't use it.
            if (data.album_info && typeof data.album_info.image !== 'string') {
                data.album_info.image = null;
            }

            song.album.info = data.album_info;

            if (cb) {
                cb();
            }
        });
    },

    /**
     * Scrobble a song (using Last.fm)
     * 
     * @param  {Object}   song
     * @param  {Function} cb 
     */
    scrobble(song, cb = null) {
        if (!sharedStore.state.useLastfm || !userStore.current().preferences.lastfm_session_key) {
            return;
        }

        http.post(`${song.id}/scrobble/${song.playStartTime}`, () => {
            if (cb) {
                cb();
            }

            return;
        });
    },
};
