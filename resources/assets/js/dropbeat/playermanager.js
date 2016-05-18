import dropbeat from './dropbeat';
import player from './player';

import $ from 'jquery';

export default {

    state: {

            youtubeType: 'youtube',
            soundcloudType: 'soundcloud',
            playing: false,
            moving: false,
            currentPlayer: null,
            currentMusic: null,
            currentMusicLength: 0,


    },

    players: {
        objs: {},
        get: function (k) {
            var that = this;

            return that.objs[k];
        },
        set: function (k, v) {
            var that = this;

            that.objs[k] = v;
        },
        pop: function (k) {
            var that = this,
                v = that.objs[k];

            delete that.objs[k];
            return v;
        },
    },

    elems: {
        loadingFilter: ".play-controls .player-initialize-filter"
    },

    init() {
        var that = this;

        var youtubePlayer,
            soundcloudPlayer,
            buttonControl;

        if ($(that.elems.loadingFilter).is(":hidden")) {
            $(that.elems.loadingFilter).show();
        }

        if (!dropbeat.state.youtubeApiReady) {
            setTimeout(function () {
                that.init();
            }, 1000);
            return;
        }

        youtubePlayer = player.create(this.state.youtubeType);
        soundcloudPlayer = player.create(this.state.soundcloudType);
        youtubePlayer.init();
        soundcloudPlayer.init();
        that.players.set(this.state.youtubeType, youtubePlayer);
        that.players.set(this.state.soundcloudType, soundcloudPlayer);

        setTimeout(function () {
            dropbeat.state.dropbeatReady = true;
            $(that.elems.loadingFilter).hide();
        }, 1000);
    },

    play(music) {
        var that = this;
        if (!music) {
            if (that.state.currentPlayer) {
                that.state.currentPlayer.play(music);
            } else {
                throw 'UndefinedError';
            }
        } else {
            if (that.state.currentPlayer && !that.state.playing) {
                if (that.state.currentPlayer.type !== music.type) {
                    // that.state.currentPlayer = that.players.get(music.type);
                }
                that.state.currentPlayer.stop();
                // playercontrol.button.setPause();
                // that.state.currentMusic = music;
                // playercontrol.progress.reset();
                // that.state.currentPlayer.play(music);
            }

            that.state.currentPlayer = that.players.get(music.type);
            // if (!that.state.currentPlayer) {
            //     return; 작동을 안함
            // }

            // playercontrol.button.setPause();
            that.state.currentMusic = music;
            // playercontrol.progress.reset();
            if (!that.state.currentPlayer.initialized) {
                that.state.currentPlayer.init(function () {
                    that.play(music);
                });
            } else {
                that.state.currentPlayer.play(music);
            }

        }

    },

    pause() {
        var that = this;

        if (that.state.currentPlayer && that.state.playing) {
            that.state.currentPlayer.pause();
        }
    },

    onPlayMusic(music) {

        var that = this;

        if (that.state.playing) {
            that.pause();
            // playercontrol.progress.stop();
            that.state.playing = false;
            return;
        }

        if (!that.state.playing) {
            if (!music) {
                if (!that.state.currentMusic) {
                    return;
                }
                music = that.state.currentMusic;
            }
            that.play(music);
            // playercontrol.progress.start();
            that.state.playing = true;
            return;
        }
    },

    onMusicClicked(music, onPlaylist) {

        var that = this;

        if (!dropbeat.state.dropbeatReady) {
            return;
        }

        if (that.isSameMusic(music)) {
            return;
        }

        that.onPlayMusic(music);

        if (!that.state.playing) {
            that.play(music);
            // playercontrol.progress.start();
            that.playing = true;
        }

        // if (onPlaylist) {
        //     var playlist;
        //
        //     이 밑으로는 플레이리스트가 존재할 때 코딩
        // }
    },

    onMusicEnd() {
        var that = this;
        that.state.playing = false;
    },

    getCurrentMusic() {
        var that = this;
        return that.state.currentMusic;
    },

    isSameMusic(music) {
        var that = this;
        return that.state.currentMusic && music.id === that.state.currentMusic.id;
    },

};
