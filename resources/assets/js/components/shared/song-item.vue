<template>
    <tr
        @dblclick.prevent="playRightAwayyyyyyy"
        class="song-item"
        :class="{ selected: selected, playing: song.playbackState === 'playing' || song.playbackState === 'paused' }"
    >
        <td class="title">
            <span class="play-count" v-if="showPlayCount"
                title="{{ song.playCount }} {{ song.playCount | pluralize 'play' }}"
                :style="{ width: song.playCount * 100 / topPlayCount + '%' }"
            >{{ song.title }}
            </span>
            <span v-else>{{ song.title }}</span>
        </td>
        <td class="artist">{{ song.album.artist.name }}</td>
        <td class="album">{{ song.album.name }}</td>
        <td class="time">{{ song.fmtLength }}</td>
        <td class="play" @click.stop="doPlayback">
            <i class="fa fa-pause-circle" v-show="song.playbackState === 'playing'"></i>
            <i class="fa fa-play-circle" v-else></i>
        </td>
    </tr>
</template>

<script>
    import playback from '../../services/playback';
    import queueStore from '../../stores/queue';

    export default {
        props: [
            'song',

            /**
             * Whether or not we should display the play count indicators.
             *
             * @type {boolean}
             */
            'showPlayCount',

            /**
             * The play count of the most-played song, so that we can have some percentage-base comparison.
             *
             * @type {integer}
             */
            'topPlayCount'
        ],

        data() {
            return {
                selected: false,
            };
        },

        methods: {
            /**
             * Play the song right away.
             */
            playRightAwayyyyyyy() {
                if (!queueStore.contains(this.song)) {
                    queueStore.queueAfterCurrent(this.song);
                }

                playback.play(this.song);
            },

            /**
             * Take the right playback action based on the current playback state.
             */
            doPlayback() {
                switch (this.song.playbackState) {
                    case 'playing':
                        playback.pause();
                        break;
                    case 'paused':
                        playback.resume();
                        break;
                    default:
                        this.playRightAwayyyyyyy();
                        break;
                }
            },

            /**
             * Toggle the "selected" state of the current component.
             */
            toggleSelectedState() {
                this.selected = !this.selected;
            },

            /**
             * Select the current component (apply a CSS class on its DOM).
             */
            select() {
                this.selected  = true;
            },

            /**
             * Deselect the current component.
             */
            deselect() {
                this.selected = false;
            }
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    .song-item {
        border-bottom: 1px solid $color2ndBgr;

        html.no-touchevents &:hover {
            background: rgba(255, 255, 255, .05);
        }

        .time {
            color: $color2ndText;
        }

        .title {
            min-width: 192px;
            padding: 0;

            span {
                display: inline-block;
                padding: 8px;

                &.play-count {
                    background: rgba(255, 255, 255, 0.08);
                    white-space: nowrap;
                }
            }
        }

        .play {
            max-width: 32px;
            opacity: .5;

            i {
                font-size: 150%;
            }
        }

        &.selected {
            background-color: rgba(255, 255, 255, .08);
        }

        &.playing {
            color: $colorHighlight;
        }

        @media only screen and (max-device-width : 768px) {
            .title {
                padding: 0;

                span {
                    display: inline;
                    padding: 0;

                    &.play-count {
                        background: none;
                    }
                }
            }
        }
    }
</style>
