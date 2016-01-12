<template>
    <footer id="mainFooter">
        <div class="side player-controls" id="playerControls">
            <i class="prev fa fa-step-backward control" @click.prevent="playPrev"></i>

            <span class="play control" v-show="!playing" @click.prevent="resume">
                <i class="fa fa-play"></i>
            </span>
            <span class="pause control" v-show="playing" @click.prevent="pause">
                <i class="fa fa-pause"></i>
            </span>

            <i class="next fa fa-step-forward control" @click.prevent="playNext"></i>
        </div>
        
        <div class="media-info-wrap">
            <div class="middle-pane">

                <span class="album-thumb" 
                    v-if="cover"
                    :style="{ backgroundImage: 'url(' + cover + ')' }">
                </span>

                <div class="progress" id="progressPane">
                    <h3 class="title">{{ song.title }}</h3>
                    <p class="meta">
                        <span class="artist">{{ song.album.artist.name }}</span> – 
                        <span class="album">{{ song.album.name }}</span>
                    </p>

                    <div class="player">
                        <audio controls></audio>
                    </div>
                </div>
            </div>
            
            <span class="other-controls" :class="{ 'with-gradient': prefs.showExtraPanel }">
                <equalizer v-if="useEqualizer" v-show="showEqualizer"></equalizer>

                <sound-bar v-show="playing"></sound-bar>

                <i class="like control fa fa-heart" :class="{ 'liked': liked }"
                    @click.prevent="like"></i>

                <span class="control" 
                    @click.prevent="toggleExtraPanel"
                    :class="{ active: prefs.showExtraPanel }">Info</span>
                
                <i class="fa fa-sliders control" 
                    v-if="useEqualizer" 
                    @click="showEqualizer = !showEqualizer"
                    :class="{ active: showEqualizer }"></i>

                <i v-else
                    class="queue control fa fa-list-ol control" 
                    :class="{ 'active': viewingQueue }"
                    @click.prevent="$root.loadMainView('queue')"></i>

                <span class="repeat control {{ prefs.repeatMode }}" @click.prevent="changeRepeatMode">
                    <i class="fa fa-repeat"></i>
                </span>
                
                <span class="volume control" id="volume">
                    <i class="fa fa-volume-up" @click.prevent="mute" v-show="!muted"></i>
                    <i class="fa fa-volume-off" @click.prevent="unmute" v-show="muted"></i>
                    <input type="range" id="volumeRange" max="10" step="0.1" v-el:volume-range class="player-volume">
                </span>
            </span>
        </div>
    </footer>
</template>

<script>
    import config from '../../config';
    import playback from '../../services/playback';
    import utils from '../../services/utils';

    import songStore from '../../stores/song';
    import favoriteStore from '../../stores/favorite';
    import preferenceStore from '../../stores/preference';

    import soundBar from '../shared/sound-bar.vue';
    import equalizer from './equalizer.vue';

    export default {
        data() {
            return { 
                song: songStore.stub,
                muted: false,
                playing: false,
                viewingQueue: false,
                liked: false,

                prefs: preferenceStore.state,
                showEqualizer: false,

                /**
                 * Indicate if we should build and use an equalizer.
                 * 
                 * @type {boolean}
                 */
                useEqualizer: utils.isAudioContextSupported(),
            };
        },

        components: { soundBar, equalizer },

        watch: {
            /**
             * Watch the current playing song and set several data attribute that will
             * affect the interface elements.
             */
            song() {
                this.liked = this.song.liked;
            },
        },

        computed: {
            /**
             * Get the album cover for the current song.
             * 
             * @return {?string}
             */
            cover() {
                // don't display the default cover here
                if (this.song.album.cover === config.unknownCover) {
                    return null;
                }

                return this.song.album.cover;
            },

            /**
             * Get the previous song in queue.
             * 
             * @return {?Object}
             */
            prev() {
                return playback.prevSong();
            },

            /**
             * Get the next song in queue.
             * 
             * @return {?Object}
             */
            next() {
                return playback.nextSong();
            },
        },

        methods: {
            /**
             * Set the volume level.
             * 
             * @param {integer}         volume  Min 0, max 10.
             * @param {boolean=true}    persist Whether the volume level should be stored into local storage.
             */
            setVolume(volume, persist = true) {
                playback.setVolume(volume, persist);
                this.muted = volume === '0' || volume === 0;
            },

            /**
             * Mute the volume.
             */
            mute() {
                return playback.mute();
            },

            /**
             * Unmute the volume.
             */
            unmute() {
                return playback.unmute();
            },

            /**
             * Play the previous song in queue.
             */
            playPrev() {
                return playback.playPrev();
            },

            /**
             * Play the next song in queue.
             */
            playNext() {
                return playback.playNext();
            },

            /**
             * Resume the current song.
             * If the current song is the stub, just play the first song in the queue.
             */
            resume() {
                if (!this.song.id) {
                    return playback.playFirstInQueue();
                }

                playback.resume();
                this.playing = true;
            },

            /**
             * <Oh God do I need to document all these methods?>
             */
            pause() {
                playback.pause();
                this.playing = false;
            },

            /**
             * <Oh well…>
             * 
             * Change the repeat mode.
             */
            changeRepeatMode() {
                return playback.changeRepeatMode();
            },

            /**
             * <Look like there's no running away from this…>
             * 
             * Like the current song.
             */
            like() {
                if (!this.song.id) {
                    return;
                }

                // Mark the song as liked/unliked right away, for a more responsive feel.
                this.liked = !this.liked;

                favoriteStore.toggleOne(this.song);
            },

            /**
             * <That's it. That's it!>
             * 
             * Toggle hide or show the extra panel.
             */
            toggleExtraPanel() {
                preferenceStore.set('showExtraPanel', !this.prefs.showExtraPanel);
            },

            /**
             * OH YISSSSSS!
             * FINALLY!
             */
        },

        events: {
            /**
             * <What…>
             *
             * Listen to song:played event and set the current playing song.
             * 
             * @param  {Object} song
             * 
             * @return {boolean}
             */
            'song:played': function (song) {
                this.playing = true;
                this.song = song;

                return true;
            },

            /**
             * <OK…>
             *
             * Listen to song:stopped event to indicate that we're not playing anymore.
             * No we're not playing anymore.
             * We're tired.
             */
            'song:stopped': function () {
                this.playing = false;
            },

            'song:paused': function () {
                this.playing = false;
            },

            /**
             * <Bye cruel world…>
             * 
             * Listen to main-content-view:load event and highlight the Queue icon if
             * the Queue screen is being loaded.
             */
            'main-content-view:load': function (view) {
                this.viewingQueue = view === 'queue';

                return true;
            },

            'koel:teardown': function () {
                this.song = songStore.stub;
                this.playing = false;
                this.liked = false;
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    @mixin hasSoftGradientOnTop($startColor) {
        position: relative;

        // Add a reverse gradient here to elimate the "hard cut" feel when the
        // song list is too long.
        &::before {
            $gradientHeight: 2*$footerHeight/3;
            content: " ";
            position: absolute;
            width: 100%;
            height: $gradientHeight;
            top: -$gradientHeight;
            left: 0;
            
            // Safari 8 won't recognize rgba(255, 255, 255, 0) and treat it as black.
            // rgba($startColor, 0) is a workaround.
            // Actually, why need I care?
            // Father always told me: Don't give a fuck about what you can't change.
            background-image: linear-gradient(to bottom, rgba($startColor, 0) 0%, rgba($startColor, 1) 100%); 
            pointer-events: none; // click-through
        }
    }

    #mainFooter {
        background: $color2ndBgr;
        position: fixed;
        width: 100%;
        height: $footerHeight;
        bottom: 0;
        left: 0;
        border-top: 1px solid $colorMainBgr;

        display: flex;
        flex: 1;
        z-index: 1000;

        .media-info-wrap {
            flex: 1;
            display: flex;
        }

        .other-controls {
            @include vertical-center();
            @include hasSoftGradientOnTop($colorMainBgr);

            &.with-gradient {
                @include hasSoftGradientOnTop($colorExtraBgr);    
            }

            text-transform: uppercase;
            flex: 0 0 $extraPanelWidth;
            color: $colorLink;

            .control {
                display: inline-block;
                padding: 0 8px;

                &.active {
                    color: $colorHighlight;
                }

                &:last-child {
                    padding-right: 0;
                }
            }

            .repeat {
                position: relative;

                &.REPEAT_ALL, &.REPEAT_ONE {
                    color: $colorHighlight;
                }

                &.REPEAT_ONE::after {
                    content: "1";
                    position: absolute;
                    top: 0;
                    left: 0;
                    font-weight: 700;
                    font-size: 50%;
                    text-align: center;
                    width: 100%;
                }
            }

            .like {
                &:hover {
                }

                &.liked {
                    color: $colorHeart;
                }
            }


            @media only screen 
            and (max-device-width : 768px) {
                position: absolute !important;
                right: 0;
                height: $footerHeight;
                display: block;
                text-align: right;
                top: 0;
                line-height: $footerHeight;
                width: 168px;
                text-align: center;

                &::before {
                    display: none;
                }

                .queue {
                    display: none;
                }

                .control {
                    margin: 0;
                    padding: 0 8px;
                }
            }
        }
    }

    #playerControls {
        @include vertical-center();
        flex: 0 0 256px;
        font-size: 24px;
        background: $colorPlayerControlsBgr;

        @include hasSoftGradientOnTop($colorSidebarBgr);

        .prev, .next {
            transition: .3s;
        }
        
        .play, .pause {
            font-size: 26px;
            display: inline-block;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            line-height: 40px;
            text-align: center;
            border: 1px solid #a0a0a0;
            margin: 0 16px;
            text-indent: 2px;
        }

        .pause {
            text-indent: 0;
            font-size: 18px;
        }

        .enabled {
            opacity: 1;
        }


        @media only screen 
        and (max-device-width : 768px) {
            width: 50%;
            position: absolute;
            top: 0;
            left: 0;

            &::before {
                display: none;
            }
        }
    }

    .middle-pane {
        flex: 1;
        display: flex;

        .album-thumb {
            flex: 0 0 $footerHeight;
            height: $footerHeight;
            background: url(/public/img/covers/unknown-album.png);
            background-size: $footerHeight;
            position: relative;
        }

        @include hasSoftGradientOnTop($colorMainBgr);


        @media only screen 
        and (max-device-width : 768px) {
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
            height: 8px;

            .album-thumb {
                display: none;
            }

            ::before {
                display: none;
            }

            #progressPane {
                width: 100%;
                position: absolute;
                top: 0;
            }
        }
    }

    #progressPane {
        flex: 1;
        text-align: center;
        padding-top: 16px;
        line-height: 18px;
        background: rgba(1, 1, 1, .2);
        position: relative;

        .meta {
            font-size: 90%;
            opacity: .4;
        }

        $blue: $colorHighlight;
        $control-color: $colorHighlight;
        $control-bg-hover: $colorHighlight;
        $volume-track-height: 8px;
        

        @import "resources/assets/sass/vendors/_plyr.scss";

        // Some little tweaks here and there
        .player {
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }

        .player-controls {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
        }

        .player-controls-left, .player-controls-right {
            display: none;
        }


        @media only screen 
        and (max-device-width : 768px) {
            .meta, .title {
                display: none;
            }

            top: -5px !important;
            padding-top: 0;
        }
    }

    #volume {
        @include vertical-center();
    
        // More tweaks
        input[type=range] {
            margin-top: -3px;
        }

        i {
            width: 16px;
        }

        @media only screen 
        and (max-device-width : 768px) {
            display: none !important;
        }
    }
</style>
