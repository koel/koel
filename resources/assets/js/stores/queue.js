import _ from 'lodash';

import songStub from '../stubs/song';

export default {
    state: {
        songs: [],
        current: songStub,
    },
    
    init() {
        // We don't have anything to do here yet.
        // How about another song then?
        // 
        // LITTLE WING
        // -- by Jimmy Fucking Hendrick
        // 
        // Well she's walking
        // Trough the clouds
        // With a circus mind
        // That's running wild
        // Butterflies and zebras and moonbeams and fairytales
        // That's all she ever thinks about
        // Riding with the wind
        // 
        // When i'm sad
        // She comes to me
        // With a thousand smiles
        // She gives to me free
        // It's alright she said
        // It's alright
        // Take anything you want from me
        // Anything...
        // 
        // [CRAZY SOLO BITCH!]
    },

    all() {
        return this.state.songs;
    },

    first() {
        return _.first(this.state.songs);
    },

    last() {
        return _.last(this.state.songs);
    },

    /**
     * Add a list of songs to the end of the current queue, 
     * or replace the current queue as a whole if `replace` is true.
     *
     * @param object|array  songs   The song, or an array of songs
     * @param bool          replace Whether to replace the current queue
     * @param bool          toTop   Whether to prepend of append to the queue
     */
    queue(songs, replace = false, toTop = false) {
        if (!Array.isArray(songs)) {
            songs = [songs];
        }

        if (replace) {
            this.state.songs = songs;
        } else {
            if (toTop) {
                this.state.songs = _.union(songs, this.state.songs);    
            } else {
                this.state.songs = _.union(this.state.songs, songs);
            }
        }
    },

    /**
     * Unqueue a song, or several songs at once.
     * 
     * @param  object|string|array songs The song(s) to unqueue.
     */
    unqueue(songs) {
        if (!Array.isArray(songs)) {
            songs = [songs];
        }

        this.state.songs = _.difference(this.state.songs, songs);
    },

    /**
     * Clear the current queue.
     */
    clear(cb = null) {
        this.state.songs = [];

        if (cb) {
            cb();
        }
    },

    /**
     * Get the next song in queue.
     * 
     * @return object|null
     */
    getNextSong() {
        var i = _.pluck(this.state.songs, 'id').indexOf(this.current().id) + 1;

        return i >= this.state.songs.length ? null : this.state.songs[i];
    },

    /**
     * Get the previous song in queue.
     * 
     * @return object|null
     */
    getPrevSong() {
        var i = _.pluck(this.state.songs, 'id').indexOf(this.current().id) - 1;

        return i < 0 ? null : this.state.songs[i];
    },

    /**
     * Get or set the current song.
     */
    current(song = null) {
        if (song) {
            this.state.current = song;
        }
        
        return this.state.current;    
    },

    /**
     * Shuffle the queue.
     */
    shuffle() {
        return (this.state.songs = _.shuffle(this.state.songs));
    },
};
