<template>
    <tr 
        @dblclick.prevent="playRighAwayyyyyyy" 
        class="song-item"
        :class="{ selected: selected, playing: playbackState === 'playing' || playbackState === 'paused' }"
    >
        <td class="title">{{ song.title }}</td>
        <td class="artist">{{ song.album.artist.name }}</td>
        <td class="album">{{ song.album.name }}</td>
        <td class="time">{{ song.fmtLength }}</td>
        <td class="play" @click.stop="doPlayback">
            <i class="fa fa-pause-circle" v-show="playbackState === 'playing'"></i>
            <i class="fa fa-play-circle" v-else></i>
        </td>
    </tr>
</template>

<script>
    import $ from 'jquery';
    import playback from '../../services/playback';
    import queueStore from '../../stores/queue';

    export default {
        props: ['song'],

        data() {
            return {
                playbackState: 'stopped',
                selected: false,
            };
        },

        methods: {
            /**
             * Play the song right away.
             */
            playRighAwayyyyyyy() {
                if (!queueStore.contains(this.song)) {
                    queueStore.queueAfterCurrent(this.song);
                }
                
                Vue.nextTick(() => playback.play(this.song));
            },

            /**
             * Take the right playback action based on the current playback state.
             */
            doPlayback() {
                switch (this.playbackState) {
                    case 'playing':
                        playback.pause();
                        break;
                    case 'paused':
                        playback.resume();
                        break;
                    default:
                        this.playRighAwayyyyyyy();
                        break;
                }
            },

            /**
             * Toggle the "selected" state of the current component.
             */
            toggleSelectedState() {
                this.selected = !this.selected;
            },

            select() {
                this.selected  = true;
            },

            deselect() {
                this.selected = false;
            }
        },

        events: {
            // Listen to playback events and set playback state.

            'song:played': function (song) {
                this.playbackState = this.song.id === song.id ? 'playing' : 'stopped';
            },

            'song:stopped': function () {
                this.playbackState = 'stopped';
            },

            'song:paused': function (song) {
                if (this.song.id === song.id) {
                    this.playbackState = 'paused';
                }
            },
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
    }
</style>
