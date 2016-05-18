import dropbeat from './dropbeat';
import playerManager from './playermanager';

import $ from 'jquery';

export default {
    create: function (type) {
        var that = this,
            player;

        if (type === 'youtube') {
            player = new that.YoutubePlayer();
        } else if (type === 'soundcloud') {
            player = new that.SoundcloudPlayer();
        } else {
            throw 'UndefinedPlayerType';
        }
        that.YoutubePlayer.prototype = new that.BaseExternalPlayer();
        that.SoundcloudPlayer.prototype = new that.BaseExternalPlayer();
        return player;
    },



    BaseExternalPlayer: function () {
        var that = this;

        that.type = undefined;

        that.initialized = false;

        that.init = function (callback) {
            throw 'NotImplementedError';
        };

        that.hide = function () {
            throw 'NotImplementedError';
        };

        that.show = function () {
            throw 'NotImplementedError';
        };

        that.play = function (music) {
            throw 'NotImplementedError';
        };

        that.stop = function () {
            throw 'NotImplementedError';
        };

        that.pause = function () {
            throw 'NotImplementedError';
        };

        that.seekTo = function (time) {
            throw 'NotImplementedError';
        };

        that.getCurrentPlaybackTime = function () {
            throw 'NotImplementedError';
        };

        that.getTotalPlaybackTime = function () {
            throw 'NotImplementedError';
        };

        that.getBuffer = function () {
            throw 'NotImplementedError';
        };

        that.calculateBuffer = function (fraction) {
            var buffer = 0;

            if (fraction) {
                buffer = fraction * 100;
                buffer = buffer > 100 ? 100 : buffer;
                buffer = buffer < 0 ? 0 : buffer;
            }
            return buffer;
        };

        that.onLoading = function () {
            // module.s.unboldTitle(); 플레이리스트가 있을 때 필요
            // module.s.playerMessage.setLoading();플레이어 컨트롤할 때 메시지
        };

        that.onFinish = function () {
            var music = null,
                playlist;

            // module.s.unboldTitle();
            playerManager.onMusicEnd();

            if (module.s.repeatControl.state
                    === module.s.repeatState.noRepeat) {
                if (module.s.shuffleControl.isShuffle()) {
                    module.s.shuffleControl.shuffle(module.s.musicQ.q);
                }
                music = module.s.musicQ.pop();
                if (music === module.constants.queueEOL) {
                    return;
                }
            } else if (module.s.repeatControl.state
                    === module.s.repeatState.repeatPlaylist) {
                if (module.s.shuffleControl.isShuffle()) {
                    module.s.shuffleControl.shuffle(module.s.musicQ.q);
                }

                music = module.s.musicQ.pop();
                if (music === module.constants.queueEOL) {
                    return;
                }
                if (!music) {
                    playlist =
                        PlaylistManager.getLocalPlaylist(
                            PlaylistManager.playingLocalSeq
                        );
                    if (module.s.shuffleControl.isShuffle()) {
                        module.s.musicQ.init(
                            module.s.shuffleControl.shuffle(
                                playlist.raw()
                            )
                        );
                    } else {
                        module.s.musicQ.init(playlist.raw());
                    }
                    music = module.s.musicQ.pop();
                }
            } else if (module.s.repeatControl.state
                    === module.s.repeatState.repeatOne) {
                music = playerManager.getCurrentMusic();
            }

            if (music) {
                playerManager.onPlayMusic(music);
            }
        };

        that.onPlaying = function () {
            var title = playerManager.state.currentMusic.title;

            // if (PlaylistManager.playingLocalSeq ===
            //         PlaylistManager.getCurrentPlaylistIdx()) {
            //     // module.s.boldTitle(title);
            // }
            // module.s.playerMessage.setTitle(title);
        };
    },

    YoutubePlayer: function () {
        var that = this;

        that.player = null;
        that.view = null;
        that.type = 'youtube';
        that.playerWidth = $('#external-player').width();
        that.playerHeight = $('#external-player').height();
        that.playing = false;
        that.isSafariHack = false;
        that.safariHackDone = false;
        that.currentVideo = null;

        that.init = function () {
            var initWidth = that.playerWidth,
                initHeight = that.playerHeight,
                options,
                videoId;

            that.isSafariHack = dropbeat.compatibility.isSafari;

// Fucking safari blocks flash plugin automatically after `mavericks`.
// (Because of fucking low energy policy)
// There may be two hacks for this problem.
// 1. Emulate automatic mouse click to force use of flash plugin in safari
// No initialize time is needed in next run of dropbeat in this solution.
// 2. When youtube frame size is large enough, youtube automatically
// acquires flash plugin permission from safari.
// Therefore, we make `500, 500` size frame to acquire flash plugin
// permission, and hide it to left by `-(widthSize - 1)`.
// If we fully hide youtube frame in this step, the whole youtube
// javascript api crashes and is not initialized. So we leave just 1px.

// We choose second magical hack to fucking safari workaround.
            if (that.isSafariHack) {
                initWidth = 500;
                initHeight = 500;
                options = {'autoplay': 1, 'controls': 0};
            } else {
                options = {'autoplay': 0, 'controls': 0};
                that.hide();
            }

            videoId = 'sUGWyrx-gCc';
            that.player = new YT.Player('youtube-player', {
                width: initWidth,
                height: initHeight,
                videoId: videoId,
                enablejsapi: 1,
                modestbranding: 1,
                playerVars: options,
                events: {
                    'onReady' : function (data) {
                        that.initialized = true;
                        that.view = $('#youtube-player');
                        data.target.addEventListener(
                            'onStateChange',
                            that.onStateChange
                        );
                    }
                }
            });
        };

        that.hide = function () {
            $('#dropbeat .external-player-section').css('left', '-539px');
        };

        that.show = function () {
            $('#dropbeat .external-player-section').css('left', '0px');
        };

        that.onStateChange = function (state) {
            if (!state) {
                return;
            }
            switch (state.data) {
            case YT.PlayerState.ENDED:
                that.onFinish();
                break;
            case YT.PlayerState.PLAYING:
                if (that.isSafariHack) {
                    that.stop();
                    that.isSafariHack = false;
                    that.view.height(that.playerHeight);
                    that.view.width(that.playerWidth);
                    that.hide();
                } else {
                    // that.onPlaying();
                }
                break;
            case YT.PlayerState.PAUSED:
                break;
            case YT.PlayerState.BUFFERING:
                break;
            case YT.PlayerState.CUED:
                break;
            case YT.PlayerState.UNSTARTED:
                if (that.currentVideo) {
                    // that.onLoading();
                }
                break;
            default:
                break;
            }
        };

        that.play = function (video) {
            var quality = 'hd720';

            if (video) {
                that.currentVideo = video;
                that.player.loadVideoById(video.id, 0, quality);
            } else {
                that.player.playVideo();
            }
        };

        that.stop = function () {
            if (that.player) {
                that.player.stopVideo();
                that.currentVideo = null;
            }
        };

        that.pause = function () {
            if (that.player) {
                that.player.pauseVideo();
            }
        };

        that.seekTo = function (time) {
            if (that.currentVideo) {
                that.player.seekTo(time, true);
            }
        };

        that.getCurrentPlaybackTime = function () {
            if (that.currentVideo) {
                return that.player.getCurrentTime();
            }
            return 0;
        };

        that.getTotalPlaybackTime = function () {
            if (that.currentVideo) {
                return that.player.getDuration();
            }
            return 0;
        };

        that.getBuffer = function () {
            if (that.player) {
                return that.calculateBuffer(
                    that.player.getVideoLoadedFraction()
                );
            }
            return 0;
        };
    },

    SoundcloudPlayer: function () {
        var that = this;

        that.streamApiBase = 'https://api.soundcloud.com/tracks/';
        that.developerKey = 'd5249ae899d7b26e6c6af608d876d12c';
        that.currentMusic = null;
        that.validationTimer = null;
        that.titleHack = false;
        that.type = 'soundcloud';

        that.playing = false;

        that.init = function (callback) {
            that.initialized = true;

            soundManager.onready(function () {
                dropbeat.state.soundManagerReady = true;
                if (callback) {
                    setTimeout(function () {
                        callback();
                    }, 1000);
                }
            });
        };

        that.hide = function () {
            $('soundcloud-player').css('left', '-10000px');
        };

        that.show = function () {
            $('soundcloud-player').css('left', '0px');
        };

        that.play = function (music) {
            var streamUrl;

            if (music) {
                streamUrl =
                    that.streamApiBase + music.id +
                    '/stream?consumer_key=' + that.developerKey;
                that.titleHack = true;
                clearTimeout(that.validationTimer);
                // that.playing = false;
                soundManager.createSound({
                    id: music.id,
                    url: streamUrl,
                    volume: 100,
                    onload: that.onStateChange,
                    onplay: that.onStateChange,
                    onfinish: that.onFinish
                });
                soundManager.play(music.id);
                that.currentMusic = music;
            } else {
                soundManager.play(that.currentMusic.id);
            }
        };

        that.stop = function () {
            soundManager.stopAll();
            if (that.currentMusic) {
                soundManager.destroySound(that.currentMusic.id);
            }
            that.currentMusic = null;
        };

        that.pause = function () {
            if (that.currentMusic) {
                soundManager.pause(that.currentMusic.id);
            }
        };

        that.seekTo = function (time) {
            if (that.currentMusic) {
                soundManager.setPosition(
                    that.currentMusic.id,
                    time * 1000
                );
            }
        };

        that.getCurrentPlaybackTime = function () {
            var playlistManager = playlistManager,
                time = 0,
                sound;

            if (that.currentMusic) {
                sound = soundManager.getSoundById(that.currentMusic.id);
                if (sound) {
                    time = sound.position / 1000.0;
                }
            }

            if (that.titleHack && time > 0) {
                if (playlistManager.playingLocalSeq
                        === playlistManager.getCurrentPlaylistIdx()) {
                    module.s.boldTitle(that.currentMusic.title);
                }
                module.s.playerMessage.setTitle(that.currentMusic.title);
                that.titleHack = false;
            }
            return time;
        };

        that.getTotalPlaybackTime = function () {
            var time = 0,
                sound;

            if (that.currentMusic) {
                sound = soundManager.getSoundById(that.currentMusic.id);
                if (sound) {
                    time = sound.durationEstimate / 1000.0;
                }
            }
            return time;
        };

        that.getBuffer = function () {
            var sound = soundManager.getSoundById(that.currentMusic.id);

            if (!that.currentMusic) {
                return 0;
            }
            if (sound) {
                return that.calculateBuffer(
                    sound.bytesLoaded / sound.bytesTotal
                );
            }
            return 0;
        };

        that.onStateChange = function (success) {
            var current = playerManager.getCurrentMusic(),
                playlist,
                onPlaylist,
                i;

            if (success !== undefined && success !== null && !success) {
                if (current && current.type === 'youtube') {
                    return;
                }

                module.s.notifyManager.notPlayable(current.title);
                if (playerManager.forth() === -1) {
                    playlist =
                        module.s.playlistManager.getCurrentPlaylist();
                    if (playlist.empty()) {
                        return;
                    }
                    onPlaylist = false;
                    for (i = 0; i < playlist.length(); i += 1) {
                        if (playlist.raw()[i].id === current.id) {
                            onPlaylist = true;
                        }
                    }
                    if (onPlaylist && playlist.raw()[0].id !== current.id) {
                        playerManager.
                            onMusicClicked(playlist.raw()[0], true);
                    }
                }

            }
            if (!that.playing) {
                that.onLoading();
                that.playing = true;
            } else {
                that.onPlaying();
            }
        };
    },




};
