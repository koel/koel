<template>
    <footer id="mainFooter">
        <div class="side player-controls" id="playerControls">
            <i class="prev fa fa-step-backward control" @click.prevent="playPrev"></i>

            <span class="play control"
                v-if="song.playbackState === 'stopped' || song.playbackState === 'paused'"
                @click.prevent="resume"
            >
                <i class="fa fa-play"></i>
            </span>
            <span class="pause control" v-else @click.prevent="pause">
                <i class="fa fa-pause"></i>
            </span>

            <i class="next fa fa-step-forward control" @click.prevent="playNext"></i>
        </div>

        <div class="media-info-wrap">
            <div class="middle-pane">

                <span class="album-thumb" v-if="cover" :style="{ backgroundImage: 'url(' + cover + ')' }"></span>

                <div class="progress" id="progressPane">
                    <h3 class="title">{{ song.title }}</h3>
                    <p class="meta">
                        <a class="artist" @click.prevent="loadArtistView(song.artist)">{{ song.artist.name }}</a> –
                        <a class="album" @click.prevent="loadAlbumView(song.album)">{{ song.album.name }}</a>
                    </p>

                    <div class="plyr">
                        <audio crossorigin="anonymous" controls></audio>
                    </div>
                </div>
            </div>

            <span class="other-controls" :class="{ 'with-gradient': prefs.showExtraPanel }">
                <equalizer v-if="useEqualizer" v-show="showEqualizer"></equalizer>

                <sound-bar v-show="song.playbackState === 'playing'"></sound-bar>

                <i class="like control fa fa-heart" :class="{ liked: song.liked }"
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
                    :class="{ active: viewingQueue }"
                    @click.prevent="loadMainView('queue')"></i>

                <span class="repeat control" :class="prefs.repeatMode" @click.prevent="changeRepeatMode">
                    <i class="fa fa-repeat"></i>
                </span>

                <span class="volume control" id="volume">
                    <i class="fa fa-volume-up" @click.prevent="mute" v-show="!muted"></i>
                    <i class="fa fa-volume-off" @click.prevent="unmute" v-show="muted"></i>
                    <input type="range" id="volumeRange" max="10" step="0.1" class="plyr__volume">
                </span>
            </span>
        </div>
    </footer>
</template>

<script>
    import config from '../../config';
    import playback from '../../services/playback';
    import { isAudioContextSupported, event, loadMainView, loadArtistView, loadAlbumView } from '../../utils';

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
                viewingQueue: false,

                prefs: preferenceStore.state,
                showEqualizer: false,

                /**
                 * Indicate if we should build and use an equalizer.
                 *
                 * @type {Boolean}
                 */
                useEqualizer: isAudioContextSupported(),
            };
        },

        components: { soundBar, equalizer },

        computed: {
            /**
             * Get the album cover for the current song.
             *
             * @return {?String}
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
                return playback.previous;
            },

            /**
             * Get the next song in queue.
             *
             * @return {?Object}
             */
            next() {
                return playback.next;
            },
        },

        methods: {
            loadMainView(v) {
                loadMainView(v);
            },

            loadArtistView(a) {
                loadArtistView(a);
            },

            loadAlbumView(a) {
                loadAlbumView(a);
            },

            /**
             * Mute the volume.
             */
            mute() {
                this.muted = true;

                return playback.mute();
            },

            /**
             * Unmute the volume.
             */
            unmute() {
                this.muted = false;

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
            },

            /**
             * Pause the playback.
             */
            pause() {
                playback.pause();
            },

            /**
             * Change the repeat mode.
             */
            changeRepeatMode() {
                return playback.changeRepeatMode();
            },

            /**
             * Like the current song.
             */
            like() {
                if (!this.song.id) {
                    return;
                }

                favoriteStore.toggleOne(this.song);
            },

            /**
             * Toggle hide or show the extra panel.
             */
            toggleExtraPanel() {
                preferenceStore.set('showExtraPanel', !this.prefs.showExtraPanel);
            },
        },

        created() {
            event.on({
                /**
                 * Listen to song:played event and set the current playing song.
                 *
                 * @param  {Object} song
                 *
                 * @return {Boolean}
                 */
                'song:played': song => this.song = song,

                /**
                 * Listen to main-content-view:load event and highlight the Queue icon if
                 * the Queue screen is being loaded.
                 */
                'main-content-view:load': view => this.viewingQueue = view === 'queue',

                'koel:teardown': () => this.song = songStore.stub,
            });
        },
    };
</script>

<style lang="sass">
    @import "../../../sass/partials/_vars.scss";
    @import "../../../sass/partials/_mixins.scss";

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
                    font-size: .5rem;
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

            @media only screen and (max-width: 768px) {
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
        font-size: 1.8rem;
        background: $colorPlayerControlsBgr;

        @include hasSoftGradientOnTop($colorSidebarBgr);

        .prev, .next {
            transition: .3s;
        }

        .play, .pause {
            font-size: 2rem;
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


        @media only screen and (max-width: 768px) {
            flex: 1;

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

        @media only screen and (max-width: 768px) {
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
            font-size: .9rem;

            a {
                &:hover {
                    color: $colorHighlight;
                }
            }
        }

        // Some little tweaks here and there
        .plyr {
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }

        .plyr__controls {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 0;
        }

        .plyr__controls--left, .plyr__controls--right {
            display: none;
        }


        @media only screen and (max-width: 768px) {
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

        @media only screen and (max-width: 768px) {
            display: none !important;
        }
    }
</style>
