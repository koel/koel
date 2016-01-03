import _ from 'lodash';
import $ from 'jquery';

import queueStore from '../stores/queue';
import songStore from '../stores/song';
import artistStore from '../stores/artist';
import albumStore from '../stores/album';
import preferenceStore from '../stores/preference';
import ls from '../services/ls';
import config from '../config';

export default {
    app: null,
    player: null,
    $volumeInput: null,
    repeatModes: ['NO_REPEAT', 'REPEAT_ALL', 'REPEAT_ONE'],
    initialized: false,

    /**
     * Initialize the playback service for this whole Koel app.
     * 
     * @param  object app The root Vue component.
     */
    init(app) {
        // We don't need to init this service twice, or the media events will be duplicated.
        if (this.initialized) {
            return;
        }

        this.app = app;

        plyr.setup({
            controls: [],
        }); 

        this.player = $('.player')[0].plyr;
        this.$volumeInput = $('#volumeRange');

        /**
         * Listen to 'error' event on the audio player and play the next song if any.
         * We don't care about network error capturing here, since every play() call
         * comes with a POST to api/interaction/play. If the user is not logged in anymore,
         * this call will result in a 401, following by a redirection to our login page.
         */
        this.player.media.addEventListener('error', e => {
            this.playNext();
        });

        /**
         * Listen to 'input' event on the volume range control.
         * When user drags the volume control, this event will be triggered, and we 
         * update the volume on the plyr object.
         */
        this.$volumeInput.on('input', e => {
            this.setVolume($(e.target).val());
        });

        // Listen to 'ended' event on the audio player and play the next song in the queue.
        this.player.media.addEventListener('ended', e => {
            songStore.scrobble(queueStore.current());
            
            if (preferenceStore.get('repeatMode') === 'REPEAT_ONE') {
                this.player.restart();
                this.player.play();

                return;
            }

            this.playNext();
        });

        // On init, set the volume to the value found in the local storage.
        this.setVolume(preferenceStore.get('volume'));

        this.initialized = true;
    },

    /**
     * Play a song. Because
     * 
     * So many adventures couldn't happen today, 
     * So many songs we forgot to play
     * So many dreams swinging out of the blue
     * We'll let them come true
     *
     * @param  object song The song to play
     */
    play(song) {
        if (!song) {
            return;
        }

        // Set the song as the current song
        queueStore.current(song);

        // Record the UNIX timestamp the song start playing, for scrobbling purpose
        song.playStartTime = Math.floor(Date.now() / 1000);

        this.app.$broadcast('song:play', song);

        $('title').text(`${song.title} ♫ Koel`);
        this.player.source(`/api/${song.id}/play?jwt-token=${ls.get('jwt-token')}`);
        this.player.play();

        // Register the play to the server
        songStore.registerPlay(song);

        // Show the notification if we're allowed to
        if (!window.Notification || !preferenceStore.get('notify')) {
            return;
        }

        try {
            var notification = new Notification(`♫ ${song.title}`, {
                icon: song.album.cover,
                body: `${song.album.name} – ${song.album.artist.name}`
            });

            notification.onclick = () => window.focus();

            // Close the notif after 5 secs.
            window.setTimeout(() => notification.close(), 5000);
        } catch (e) {
            // Notification fails. 
            // @link https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration/showNotification
        }
    },

    /**
     * Get the next song in the queue. 
     * If we're in REPEAT_ALL mode and there's no next song, just get the first song.
     */
    nextSong() {
        var next = queueStore.getNextSong();

        if (next) {
            return next;
        }

        if (preferenceStore.get('repeatMode') === 'REPEAT_ALL') {
            return queueStore.first();
        }
    },

    /**
     * Get the prev song in the queue.
     * If we're in REPEAT_ALL mode and there's no prev song, get the last song.
     */
    prevSong() {
        var prev = queueStore.getPrevSong();

        if (prev) {
            return prev;
        }

        if (preferenceStore.get('repeatMode') === 'REPEAT_ALL') {
            return queueStore.last();
        }
    },

    /**
     * Circle through the repeat mode.
     * The selected mode will be stored into local storage as well.
     */
    changeRepeatMode() {
        var i = this.repeatModes.indexOf(preferenceStore.get('repeatMode')) + 1;
                    
        if (i >= this.repeatModes.length) {
            i = 0;
        }
        
        preferenceStore.set('repeatMode',  this.repeatModes[i]);
    },

    /**
     * Play the prev song in the queue, if one is found.
     * If the prev song is not found and the current mode is NO_REPEAT, we stop completely.
     */
    playPrev() {
        // If the song's duration is greater than 5 seconds and we've passed 5 seconds into it
        // restart playing instead.
        if (this.player.media.currentTime > 5 && this.player.media.duration > 5) {
            this.player.seek(0);

            return;            
        }

        var prev = this.prevSong();

        if (!prev && preferenceStore.get('repeatMode') === 'NO_REPEAT') {
            this.stop();

            return;
        }

        this.play(prev);
    },

    /**
     * Play the next song in the queue, if one is found.
     * If the next song is not found and the current mode is NO_REPEAT, we stop completely.
     */
    playNext() {
        var next = this.nextSong();

        if (!next && preferenceStore.get('repeatMode') === 'NO_REPEAT') {
            //  Nothing lasts forever, even cold November rain.
            this.stop();

            return;
        }

        this.play(next);
    },

    /**
     * Set the volume level.
     * 
     * @param integer  volume   0-10
     * @param boolean  persist  Whether the volume should be saved into local storage
     */
    setVolume(volume, persist = true) {
        this.player.setVolume(volume);

        if (persist) {
            preferenceStore.set('volume', volume);
        }

        this.$volumeInput.val(volume);
    },

    /**
     * Mute playback.
     */
    mute() {
        this.setVolume(0, false);
    },

    /**
     * Unmute playback.
     */
    unmute() {
        // If the saved volume is 0, we unmute to the default level (7).
        if (preferenceStore.get('volume') === '0' || preferenceStore.get('volume') === 0) {
            preferenceStore.set('volume', 7);
        }

        this.setVolume(preferenceStore.get('volume'));
    },

    /**
     * Completely stop playback.
     */
    stop() {
        $('title').text(config.appTitle);
        this.player.pause();
        this.player.seek(0);

        this.app.$broadcast('song:stop');
    },

    /**
     * Pause playback.
     */
    pause() {
        this.player.pause();
    },

    /**
     * Resume playback.
     */
    resume() {
        this.player.play();
    },

    /**
     * Queue up songs (replace them into the queue) and start playing right away.
     *
     * @param array|null    songs   An array of song objects. Defaults to all songs if null.
     * @param bool          shuffle Whether to shuffle the songs before playing.
     */
    queueAndPlay(songs = null, shuffle = false) {
        if (!songs) {
            songs = songStore.all();
        }

        if (!songs.length) {
            return;
        }

        if (shuffle) {
            songs = _.shuffle(songs);
        }

        queueStore.clear();

        queueStore.queue(songs, true);

        this.app.loadMainView('queue');

        // Wrap this inside a nextTick() to wait for the DOM to complete updating
        // and then play the first song in the queue.
        Vue.nextTick(() => this.play(queueStore.first()));
    },

    /**
     * Play the first song in the queue.
     * If the current queue is empty, try creating it by shuffling all songs.
     */
    playFirstInQueue() {
        if (!queueStore.all().length) {
            this.queueAndPlay();

            return;
        }

        this.play(queueStore.first());
    },

    /**
     * Play all songs by an artist.
     * 
     * @param  {Object}  artist  The artist object
     * @param  {Boolean} shuffle Whether to shuffle the songs
     */
    playAllByArtist(artist, shuffle = true) {
        this.queueAndPlay(artistStore.getSongsByArtist(artist), true);
    },

    /**
     * Play all songs in an album.
     * 
     * @param  {Object}  album   The album object
     * @param  {Boolean} shuffle Whether to shuffle the songs
     */
    playAllInAlbum(album, shuffle = true) {
        this.queueAndPlay(album.songs, true);
    },
};
