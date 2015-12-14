import _ from 'lodash';

import http from '../services/http';
import utils from '../services/utils';
import stub from '../stubs/song';
import albumStore from './album';
import favoriteStore from './favorite';
import sharedStore from './shared';

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
     * Get a song's lyrics.
     * A HTTP request will be made if the song has no lyrics attribute yet.
     * 
     * @param  object   song
     * @param  function cb
     */
    getLyrics(song, cb = null) {
        if (!_.isUndefined(song.lyrics)) {
            if (cb) {
                cb();
            }
            
            return;
        }

        http.get(`${song.id}/lyrics`, lyrics => {
            song.lyrics = lyrics;

            if (cb) {
                cb();
            }
        });
    }
};
