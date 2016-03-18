import _ from 'lodash';

export default {
    state: {
        songs: [],
        current: null,
    },

    init() {
        // We don't have anything to do here yet.
        // How about another song then?
        //
        // LITTLE WING
        // -- by Jimi Fucking Hendrix
        //
        // Well she's walking
        // Through the clouds
        // With a circus mind
        // That's running wild
        // Butterflies and zebras and moonbeams and fairytales
        // That's all she ever thinks about
        // Riding with the wind
        //
        // When I'm sad
        // She comes to me
        // With a thousand smiles
        // She gives to me free
        // It's alright she said
        // It's alright
        // Take anything you want from me
        // Anything...
    },

    /**
     * All queued songs.
     *
     * @return {Array.<Object>}
     */
    get all() {
        return this.state.songs;
    },

    /**
     * The first song in the queue.
     *
     * @return {?Object}
     */
    get first() {
        return _.first(this.state.songs);
    },

    /**
     * The last song in the queue.
     *
     * @return {?Object}
     */
    get last() {
        return _.last(this.state.songs);
    },

    /**
     * Determine if the queue contains a song.
     *
     * @param  {Object} song
     *
     * @return {Boolean}
     */
    contains(song) {
        return _.includes(this.all, song);
    },

    /**
     * Add a list of songs to the end of the current queue,
     * or replace the current queue as a whole if `replace` is true.
     *
     * @param {Object|Array.<Object>}   songs   The song, or an array of songs
     * @param {Boolean}                 replace Whether to replace the current queue
     * @param {Boolean}                 toTop   Whether to prepend or append to the queue
     */
    queue(songs, replace = false, toTop = false) {
        songs = [].concat(songs);

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
     * Queue song(s) to after the current song.
     *
     * @param  {Array.<Object>|Object} songs
     */
    queueAfterCurrent(songs) {
        songs = [].concat(songs);

        if (!this.state.current || !this.state.songs.length) {
            return this.queue(songs);
        }

        let head = this.state.songs.splice(0, this.indexOf(this.state.current) + 1);
        this.state.songs = head.concat(songs, this.state.songs);
    },

    /**
     * Unqueue a song, or several songs at once.
     *
     * @param  {Object|String|Array.<Object>} songs The song(s) to unqueue
     */
    unqueue(songs) {
        this.state.songs = _.difference(this.state.songs, [].concat(songs));
    },

    /**
     * Move some songs to after a target.
     *
     * @param  {Array.<Object>} songs  Songs to move
     * @param  {Object}         target The target song object
     */
    move(songs, target) {
        let $targetIndex = this.indexOf(target);

        songs.forEach(song => {
            this.state.songs.splice(this.indexOf(song), 1);
            this.state.songs.splice($targetIndex, 0, song);
        });
    },

    /**
     * Clear the current queue.
     *
     * @param {?Function} cb The function to execute after clearing
     */
    clear(cb = null) {
        this.state.songs = [];
        this.state.current = null;

        if (cb) {
            cb();
        }
    },

    /**
     * Get index of a song in the queue.
     *
     * @param  {Object} song
     *
     * @return {?Integer}
     */
    indexOf(song) {
        return _.indexOf(this.state.songs, song);
    },

    /**
     * The next song in queue.
     *
     * @return {?Object}
     */
    get next() {
        if (!this.current) {
            return _.first(this.state.songs);
        }

        let i = _.pluck(this.state.songs, 'id').indexOf(this.current.id) + 1;

        return i >= this.state.songs.length ? null : this.state.songs[i];
    },

    /**
     * The previous song in queue.
     *
     * @return {?Object}
     */
    get previous() {
        if (!this.current) {
            return _.last(this.state.songs);
        }

        let i = _.pluck(this.state.songs, 'id').indexOf(this.current.id) - 1;

        return i < 0 ? null : this.state.songs[i];
    },

    /**
     * The current song.
     *
     * @return {Object}
     */
    get current() {
        return this.state.current;
    },

    /**
     * Set a song as the current queued song.
     *
     * @param  {Object} song
     *
     * @return {Object} The queued song.
     */
    set current(song) {
        this.state.current = song;

        return this.current;
    },

    /**
     * Shuffle the queue.
     *
     * @return {Array.<Object>} The shuffled array of song objects
     */
    shuffle() {
        return (this.state.songs = _.shuffle(this.state.songs));
    },
};
